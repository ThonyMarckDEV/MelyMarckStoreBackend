<?php

namespace App\Http\Controllers;

use App\Models\ImagenModelo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PedidoDetalle;
use App\Models\CarritoDetalle;
use App\Models\DetalleDireccion;
use App\Models\Pedido;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PedidosController extends Controller
{
    /**
     * Create a new order from cart details.
     */
    public function createOrder(Request $request)
    {
        try {
            $request->validate([
                'idCarrito' => 'required|exists:carrito,idCarrito',
                'pickupMethod' => 'required|in:delivery,store',
                'idDireccion' => 'required_if:pickupMethod,delivery|exists:detalle_direcciones,idDireccion',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.'
                ], 401);
            }

            $idCarrito = $request->input('idCarrito');
            $pickupMethod = $request->input('pickupMethod');
            $idDireccion = $request->input('idDireccion');

            $cartDetails = CarritoDetalle::where('idCarrito', $idCarrito)
                ->with('producto', 'modelo')
                ->get();

            if ($cartDetails->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El carrito está vacío.'
                ], 400);
            }

            $total = $cartDetails->sum(function ($detail) {
                return $detail->subtotal;
            });

            DB::beginTransaction();

            $orderData = [
                'idUsuario' => $user->idUsuario,
                'total' => $total,
                'estado' => 0,
                'recojo_local' => $pickupMethod === 'store' ? 1 : 0,
                'fecha_pedido' => now(),
                'departamento' => '',
                'distrito' => '',
                'provincia' => '',
                'direccion_shalom' => '',
            ];

            if ($pickupMethod === 'delivery') {
                $address = DetalleDireccion::where('idDireccion', $idDireccion)
                    ->where('idUsuario', $user->idUsuario)
                    ->first();

                if (!$address) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Dirección no válida.'
                    ], 400);
                }

                $orderData['departamento'] = $address->departamento;
                $orderData['distrito'] = $address->distrito;
                $orderData['provincia'] = $address->provincia;
                $orderData['direccion_shalom'] = $address->direccion_shalom;
            }

            $pedido = Pedido::create($orderData);

            foreach ($cartDetails as $detail) {
                PedidoDetalle::create([
                    'idPedido' => $pedido->idPedido,
                    'idProducto' => $detail->idProducto,
                    'idModelo' => $detail->idModelo,
                    'cantidad' => $detail->cantidad,
                    'precioUnitario' => $detail->producto->precio,
                    'subtotal' => $detail->subtotal,
                ]);
            }

            CarritoDetalle::where('idCarrito', $idCarrito)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente.',
                'data' => [
                    'idPedido' => $pedido->idPedido,
                    'total' => number_format($pedido->total, 2),
                    'estado' => $this->getEstadoText($pedido->estado),
                    'recojo_local' => $pedido->recojo_local,
                    'fecha_pedido' => $pedido->fecha_pedido->format('d/m/Y H:i'),
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch all orders for the authenticated user with details
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $pedidos = Pedido::where('idUsuario', $user->idUsuario)
                ->orderBy('fecha_pedido', 'desc')
                ->with(['detalles.producto', 'detalles.modelo']) // Eager load details
                ->get()
                ->map(function ($pedido) {
                    $fechaPedido = is_string($pedido->fecha_pedido)
                        ? Carbon::parse($pedido->fecha_pedido)
                        : $pedido->fecha_pedido;

                    $detalles = $pedido->detalles->map(function ($detalle) {
                        $imagen = ImagenModelo::where('idModelo', $detalle->idModelo)
                            ->first();

                        return [
                            'idDetallePedido' => $detalle->idDetallePedido,
                            'producto' => [
                                'idProducto' => $detalle->producto->idProducto,
                                'nombreProducto' => $detalle->producto->nombreProducto,
                                'precio' => number_format($detalle->precioUnitario, 2),
                            ],
                            'modelo' => [
                                'idModelo' => $detalle->modelo->idModelo,
                                'nombreModelo' => $detalle->modelo->nombreModelo,
                                'imagen' => $imagen ? $imagen->urlImagen : null,
                            ],
                            'cantidad' => $detalle->cantidad,
                            'subtotal' => number_format($detalle->subtotal, 2),
                        ];
                    });

                    return [
                        'idPedido' => $pedido->idPedido,
                        'total' => number_format($pedido->total, 2),
                        'estado' => $this->getEstadoText($pedido->estado),
                        'recojo_local' => $pedido->recojo_local,
                        'direccion' => $pedido->recojo_local ? 'Recojo en tienda' :
                            "{$pedido->direccion_shalom}, {$pedido->distrito}, {$pedido->provincia}, {$pedido->departamento}",
                        'fecha_pedido' => $fechaPedido->format('d/m/Y H:i'),
                        'detalles' => $detalles,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $pedidos,
                'message' => 'Pedidos obtenidos correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pedidos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper function to convert estado code to text
     */
    private function getEstadoText($estado)
    {
        $estados = [
            0 => 'Pendiente de pago',
            1 => 'Aprobando pago',
            2 => 'En preparación',
            3 => 'Enviado',
            4 => 'Listo para recoger',
            5 => 'Cancelado',
        ];
        return $estados[$estado] ?? 'Desconocido';
    }
}