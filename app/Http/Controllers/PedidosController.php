<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PedidoDetalle;
use App\Models\CarritoDetalle;
use App\Models\DetalleDireccion;
use App\Models\Pedido;
use Illuminate\Support\Facades\Auth;

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
}