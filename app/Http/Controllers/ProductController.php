<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Modelo;
use App\Models\Stock;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller

{
    public function index(Request $request)
    {
        try {
            $perPage = 4;
            $page = $request->query('page', 1);
            $subcategoryId = $request->query('subcategory_id');
            $categoryId = $request->query('category_id');
            $name = $request->query('name');
            $minPrice = $request->query('min_price');
            $maxPrice = $request->query('max_price');

            $query = Producto::with(['modelos.stock', 'modelos.imagenes', 'caracteristicasProducto'])
                ->where('estado', true);

            // Filter by subcategory_id
            if ($subcategoryId) {
                $query->where('idSubCategoria', $subcategoryId);
            }

            // Filter by category_id (via subcategorias relationship)
            if ($categoryId) {
                $query->whereHas('subcategoria', function ($q) use ($categoryId) {
                    $q->where('idCategoria', $categoryId);
                });
            }

            // Filter by name (partial match)
            if ($name) {
                $query->where('nombreProducto', 'like', "%$name%");
            }

            // Filter by price range
            if ($minPrice !== null) {
                $query->where('precio', '>=', $minPrice);
            }
            if ($maxPrice !== null) {
                $query->where('precio', '<=', $maxPrice);
            }

            $products = $query->paginate($perPage, ['*'], 'page', $page);

            $formattedProducts = $products->map(function ($product) {
                return [
                    'idProducto' => $product->idProducto,
                    'nombreProducto' => $product->nombreProducto,
                    'descripcion' => $product->descripcion,
                    'precio' => (float) $product->precio,
                    'caracteristicas' => $product->caracteristicasProducto ? $product->caracteristicasProducto->caracteristicas : null,
                    'modelos' => $product->modelos->map(function ($modelo) {
                        Log::info('Modelo Data:', [
                            'idModelo' => $modelo->idModelo,
                            'stock_type' => $modelo->stock ? get_class($modelo->stock) : 'null',
                            'stock_data' => $modelo->stock ? $modelo->stock->toArray() : null,
                            'imagenes' => $modelo->imagenes->toArray(),
                        ]);

                        $stock = $modelo->stock;
                        $imagenes = $modelo->imagenes->isNotEmpty() 
                            ? $modelo->imagenes->map(function ($imagen) {
                                return [
                                    'idImagen' => $imagen->idImagen,
                                    'urlImagen' => $imagen->urlImagen ?? 'https://salonlfc.com/wp-content/uploads/2018/01/image-not-found-scaled.png',
                                ];
                            })->toArray()
                            : [[
                                'idImagen' => null,
                                'urlImagen' => 'https://salonlfc.com/wp-content/uploads/2018/01/image-not-found-scaled.png',
                            ]];

                        return [
                            'idModelo' => $modelo->idModelo,
                            'nombreModelo' => $modelo->nombreModelo,
                            'stock' => $stock ? [
                                'idStock' => $stock->idStock ?? null,
                                'cantidad' => $stock->cantidad ?? 0,
                            ] : [
                                'idStock' => null,
                                'cantidad' => 0,
                            ],
                            'imagenes' => $imagenes,
                        ];
                    })->toArray(),
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $formattedProducts,
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener productos:', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage(),
            ], 500);
        }
    }
}