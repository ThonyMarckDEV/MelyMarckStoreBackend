<?php

namespace App\Http\Controllers;

use App\Models\CarritoDetalle;
use App\Models\Modelo;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DetalleCarritoController extends Controller
{
    public function index($idCarrito)
    {
        try {
            $cartDetails = CarritoDetalle::where('idCarrito', $idCarrito)
                ->with([
                    'producto',
                    'modelo' => function ($query) {
                        $query->with(['imagenes', 'stock', 'producto']);
                    }
                ])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $cartDetails
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles del carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $idCarrito)
    {
        $validator = Validator::make($request->all(), [
            'idProducto' => 'required|exists:productos,idProducto',
            'idModelo' => 'required|exists:modelos,idModelo',
            'cantidad' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $stock = Stock::where('idModelo', $request->idModelo)->first();
            if (!$stock || $stock->cantidad < $request->cantidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente'
                ], 400);
            }

            $modelo = Modelo::find($request->idModelo);
            $producto = $modelo->producto;
            $subtotal = $producto->precio * $request->cantidad;

            $detalle = CarritoDetalle::create([
                'idCarrito' => $idCarrito,
                'idProducto' => $request->idProducto,
                'idModelo' => $request->idModelo,
                'cantidad' => $request->cantidad,
                'subtotal' => $subtotal,
            ]);

            return response()->json([
                'success' => true,
                'data' => $detalle
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar al carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $idDetalle)
    {
        $validator = Validator::make($request->all(), [
            'cantidad' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $detalle = CarritoDetalle::findOrFail($idDetalle);
            $stock = Stock::where('idModelo', $detalle->idModelo)->first();

            if (!$stock || $stock->cantidad < $request->cantidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente'
                ], 400);
            }

            $modelo = Modelo::find($detalle->idModelo);
            $producto = $modelo->producto;
            $detalle->cantidad = $request->cantidad;
            $detalle->subtotal = $producto->precio * $request->cantidad;
            $detalle->save();

            return response()->json([
                'success' => true,
                'data' => $detalle
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el detalle: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($idDetalle)
    {
        try {
            $detalle = CarritoDetalle::findOrFail($idDetalle);
            $detalle->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto: ' . $e->getMessage()
            ], 500);
        }
    }
}
?>