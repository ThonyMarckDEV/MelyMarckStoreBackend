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
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PedidosController extends Controller
{
    /**
     * Create a new order from cart details.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
    {
        try {
            // Validate request data
            $request->validate([
                'idCarrito' => 'required|exists:carrito,idCarrito',
                'pickupMethod' => 'required|in:delivery,store',
                'idDireccion' => 'required_if:pickupMethod,delivery|exists:detalle_direcciones,idDireccion',
            ]);

            // Get authenticated user
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

            // Fetch cart details
            $cartDetails = CarritoDetalle::where('idCarrito', $idCarrito)
                ->with('producto', 'modelo')
                ->get();

            if ($cartDetails->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El carrito está vacío.'
                ], 400);
            }

            // Calculate total
            $total = $cartDetails->sum(function ($detail) {
                return $detail->subtotal;
            });

            // Start database transaction
            DB::beginTransaction();

            // Prepare order data
            $orderData = [
                'idUsuario' => $user->idUsuario,
                'total' => $total,
                'estado' => 0, // Pending payment
                'recojo_local' => $pickupMethod === 'store' ? 1 : 0,
                'fecha_pedido' => now(),
            ];

            // If delivery, fetch address details
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
            } else {
                // Store pickup: set address fields to empty or null
                $orderData['departamento'] = '';
                $orderData['distrito'] = '';
                $orderData['provincia'] = '';
                $orderData['direccion_shalom'] = '';
            }

            // Create order
            $pedido = Pedido::create($orderData);

            // Transfer cart details to pedido_detalle
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

            // Delete cart details
            CarritoDetalle::where('idCarrito', $idCarrito)->delete();

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente.',
                'data' => [
                    'idPedido' => $pedido->idPedido,
                    'total' => $pedido->total,
                    'estado' => $pedido->estado,
                    'recojo_local' => $pedido->recojo_local,
                    'fecha_pedido' => $pedido->fecha_pedido,
                ]
            ], 201);

        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch all orders for the authenticated user
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $pedidos = Pedido::where('idUsuario', $user->idUsuario)
                ->orderBy('fecha_pedido', 'desc')
                ->get()
                ->map(function ($pedido) {
                    // Generate QR code for the order ID
                    $qrCode = QrCode::format('png')
                        ->size(200)
                        ->generate($pedido->idPedido);

                    return [
                        'idPedido' => $pedido->idPedido,
                        'total' => number_format($pedido->total, 2),
                        'estado' => $this->getEstadoText($pedido->estado),
                        'recojo_local' => $pedido->recojo_local,
                        'direccion' => $pedido->recojo_local ? 'Recojo en tienda' : 
                            "{$pedido->direccion_shalom}, {$pedido->distrito}, {$pedido->provincia}, {$pedido->departamento}",
                        'fecha_pedido' => $pedido->fecha_pedido->format('d/m/Y H:i'),
                        'qr_code' => base64_encode($qrCode), // Base64 encoded QR code image
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
     * Fetch details for a specific order
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $pedido = Pedido::where('idUsuario', $user->idUsuario)
                ->where('idPedido', $id)
                ->first();

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }

            $detalles = PedidoDetalle::where('idPedido', $id)
                ->with(['producto', 'modelo'])
                ->get()
                ->map(function ($detalle) {
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

            return response()->json([
                'success' => true,
                'data' => [
                    'idPedido' => $pedido->idPedido,
                    'total' => number_format($pedido->total, 2),
                    'estado' => $this->getEstadoText($pedido->estado),
                    'recojo_local' => $pedido->recojo_local,
                    'direccion' => $pedido->recojo_local ? 'Recojo en tienda' : 
                        "{$pedido->direccion_shalom}, {$pedido->distrito}, {$pedido->provincia}, {$pedido->departamento}",
                    'fecha_pedido' => $pedido->fecha_pedido->format('d/m/Y H:i'),
                    'detalles' => $detalles,
                ],
                'message' => 'Detalles del pedido obtenidos correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles del pedido: ' . $e->getMessage(),
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