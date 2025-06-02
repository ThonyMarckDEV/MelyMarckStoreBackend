<?php

namespace App\Http\Controllers;

use App\Models\SubCategoria;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubCategoriesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $categoryId = $request->query('category_id');
            $query = SubCategoria::where('estado', true)
                ->select('idSubCategoria', 'nombreSubCategoria', 'idCategoria', 'estado');

            if ($categoryId) {
                $query->where('idCategoria', $categoryId);
            }

            $subcategories = $query->with(['categoria:idCategoria,nombreCategoria'])
                ->get()
                ->map(function ($subcategory) {
                    return [
                        'idSubCategoria' => $subcategory->idSubCategoria,
                        'nombreSubCategoria' => $subcategory->nombreSubCategoria ?? '',
                        'idCategoria' => $subcategory->idCategoria,
                        'nombreCategoria' => $subcategory->categoria->nombreCategoria ?? '',
                        'estado' => $subcategory->estado
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $subcategories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching active subcategories: ' . $e->getMessage()
            ], 500);
        }
    }

    public function indexAdmin(Request $request)
    {
        try {
            $categoryId = $request->query('category_id');
            $query = SubCategoria::select('idSubCategoria', 'nombreSubCategoria', 'idCategoria', 'estado');

            if ($categoryId) {
                $query->where('idCategoria', $categoryId);
            }

            $subcategories = $query->with(['categoria:idCategoria,nombreCategoria'])
                ->get()
                ->map(function ($subcategory) {
                    return [
                        'idSubCategoria' => $subcategory->idSubCategoria,
                        'nombreSubCategoria' => $subcategory->nombreSubCategoria ?? '',
                        'idCategoria' => $subcategory->idCategoria,
                        'nombreCategoria' => $subcategory->categoria->nombreCategoria ?? '',
                        'estado' => $subcategory->estado
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $subcategories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching subcategories: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombreSubCategoria' => 'required|string|max:255',
                'idCategoria' => 'required|exists:categorias,idCategoria',
            ]);

            $subcategory = new SubCategoria();
            $subcategory->nombreSubCategoria = $validated['nombreSubCategoria'];
            $subcategory->idCategoria = $validated['idCategoria'];
            $subcategory->estado = true;
            $subcategory->save();

            return response()->json([
                'success' => true,
                'message' => 'Subcategory created successfully',
                'data' => [
                    'idSubCategoria' => $subcategory->idSubCategoria,
                    'nombreSubCategoria' => $subcategory->nombreSubCategoria,
                    'idCategoria' => $subcategory->idCategoria,
                    'estado' => $subcategory->estado
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating subcategory', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating subcategory: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $subcategory = SubCategoria::findOrFail($id);

            $validated = $request->validate([
                'nombreSubCategoria' => 'required|string|max:255',
                'idCategoria' => 'required|exists:categorias,idCategoria',
            ]);

            $hasChanges = false;
            $newName = trim($request->input('nombreSubCategoria'));
            if (!empty($newName) && $newName !== $subcategory->nombreSubCategoria) {
                $subcategory->nombreSubCategoria = $newName;
                $hasChanges = true;
            }
            if ($validated['idCategoria'] !== $subcategory->idCategoria) {
                $subcategory->idCategoria = $validated['idCategoria'];
                $hasChanges = true;
            }

            if (!$hasChanges) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid changes provided.'
                ], 400);
            }

            $subcategory->save();

            return response()->json([
                'success' => true,
                'message' => 'Subcategory updated successfully',
                'data' => [
                    'idSubCategoria' => $subcategory->idSubCategoria,
                    'nombreSubCategoria' => $subcategory->nombreSubCategoria,
                    'idCategoria' => $subcategory->idCategoria,
                    'estado' => $subcategory->estado
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating subcategory', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating subcategory: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $subcategory = SubCategoria::findOrFail($id);

            $validated = $request->validate([
                'active' => 'required|boolean',
            ]);

            $subcategory->estado = $validated['active'];
            $subcategory->save();

            return response()->json([
                'success' => true,
                'message' => 'Subcategory status updated successfully',
                'data' => [
                    'idSubCategoria' => $subcategory->idSubCategoria,
                    'nombreSubCategoria' => $subcategory->nombreSubCategoria,
                    'idCategoria' => $subcategory->idCategoria,
                    'estado' => $subcategory->estado
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling subcategory status: ' . $e->getMessage()
            ], 500);
        }
    }
}
