<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\CarritoDetalle;
use App\Models\Modelo;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class CarritoController extends Controller
{
    public function addToCarrito(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idCarrito' => 'required|exists:carrito,idCarrito',
            'idProducto' => 'required|exists:productos,idProducto',
            'idModelo' => 'required|exists:modelos,idModelo',
            'cantidad' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $user = auth()->user();
            $carrito = Carrito::find($request->idCarrito);
            if (!$carrito || $carrito->idUsuario != $user->idUsuario) {
                return response()->json(['message' => 'Carrito no válido'], 403);
            }

            // Verificar que idModelo pertenece a idProducto
            $modelo = Modelo::with('stock')
                ->where('idModelo', $request->idModelo)
                ->where('idProducto', $request->idProducto)
                ->first();
            if (!$modelo) {
                return response()->json(['message' => 'El modelo no pertenece al producto especificado'], 400);
            }

            // Verificar stock
            $stockCantidad = $modelo->stock ? $modelo->stock->cantidad : 0;
            if ($stockCantidad < $request->cantidad) {
                return response()->json(['message' => 'Stock insuficiente'], 400);
            }

            // Buscar si ya existe un detalle con el mismo producto y modelo en el carrito
            $carritoDetalle = CarritoDetalle::where('idCarrito', $request->idCarrito)
                ->where('idProducto', $request->idProducto)
                ->where('idModelo', $request->idModelo)
                ->first();

            // Calcular subtotal
            $producto = Producto::find($request->idProducto);
            $subtotal = $producto->precio * $request->cantidad;

            if ($carritoDetalle) {
                // Si existe, actualizar la cantidad y el subtotal
                $newCantidad = $carritoDetalle->cantidad + $request->cantidad;
                if ($newCantidad > $stockCantidad) {
                    return response()->json(['message' => 'Stock insuficiente para la cantidad total'], 400);
                }
                $carritoDetalle->update([
                    'cantidad' => $newCantidad,
                    'subtotal' => $producto->precio * $newCantidad,
                ]);
            } else {
                // Si no existe, crear un nuevo detalle
                CarritoDetalle::create([
                    'idCarrito' => $request->idCarrito,
                    'idProducto' => $request->idProducto,
                    'idModelo' => $request->idModelo,
                    'cantidad' => $request->cantidad,
                    'subtotal' => $subtotal,
                ]);
            }

            return response()->json(['message' => 'Producto agregado al carrito'], 200);
        } catch (\Exception $e) {
            Log::error('Error in addToCarrito: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al agregar el producto al carrito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCartItemCount($idCarrito)
    {
        try {
            $user = auth()->user();
            $carrito = Carrito::find($idCarrito);
            if (!$carrito || $carrito->idUsuario != $user->idUsuario) {
                return response()->json(['message' => 'Carrito no válido'], 403);
            }

            $totalItems = CarritoDetalle::where('idCarrito', $idCarrito)
                ->sum('cantidad');

            return response()->json(['totalItems' => $totalItems], 200);
        } catch (\Exception $e) {
            \Log::error('Error in getCartItemCount: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener la cantidad de productos en el carrito',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}