<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PaymentController extends Controller
{
    /**
     * Handle the upload of payment receipt and create a payment record.
     */
    public function uploadReceipt(Request $request)
    {
        try {
            $request->validate([
                'orderId' => 'required|exists:pedidos,idPedido',
                'paymentMethod' => 'required|in:Depósito Bancario,Yape',
                'receipt' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Max 2MB
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.'
                ], 401);
            }

            $orderId = $request->input('orderId');
            $paymentMethod = $request->input('paymentMethod');

            // Verify the order belongs to the user and is in 'Pendiente de pago' state
            $order = Pedido::where('idPedido', $orderId)
                ->where('idUsuario', $user->idUsuario)
                ->where('estado', 0) // 0 = Pendiente de pago
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado o no está en estado pendiente de pago.'
                ], 400);
            }

            // Handle file upload
            $receiptFile = $request->file('receipt');
            $fileName = time() . '_' . $receiptFile->getClientOriginalName();
            $path = public_path("storage/{$user->idUsuario}/orders/{$orderId}/payment");

            // Create directory if it doesn't exist
            File::makeDirectory($path, 0755, true, true);

            // Move the file to the public directory
            $receiptFile->move($path, $fileName);

            // Generate the URL for the stored file
            $receiptUrl = "storage/{$user->idUsuario}/orders/{$orderId}/payment/{$fileName}";

            DB::beginTransaction();

            // Create payment record
            $pago = Pago::create([
                'idPedido' => $order->idPedido,
                'monto' => $order->total,
                'metodo_pago' => $paymentMethod,
                'estado_pago' => 'Pendiente', // Initial payment status
                'comprobante_url' => $receiptUrl,
                'fecha_pago' => now(),
            ]);

            // Update order status to 'Aprobando pago' (estado = 1)
            $order->update([
                'estado' => 1, // Aprobando pago
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comprobante enviado exitosamente. En espera de verificación.',
                'data' => [
                    'idPago' => $pago->idPago,
                    'idPedido' => $order->idPedido,
                    'estado_pedido' => $this->getEstadoText($order->estado),
                    'estado_pago' => $pago->estado_pago,
                    'comprobante_url' => $pago->comprobante_url,
                    'fecha_pago' => $pago->fecha_pago->format('d/m/Y H:i'),
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el comprobante: ' . $e->getMessage()
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