<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoriesController extends Controller
{
   public function index()
    {
        try {
            $categories = Categoria::active()
                ->select('idCategoria', 'nombreCategoria', 'imagen', 'estado')
                ->get();
            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching active categories: ' . $e->getMessage()
            ], 500);
        }
    }

    public function indexAdmin()
    {
        try {
            $categories = Categoria::select('idCategoria', 'nombreCategoria', 'imagen', 'estado')
                ->get();
            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching all categories: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombreCategoria' => 'required|string|max:255',
                'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $category = new Categoria();
            $category->nombreCategoria = $validated['nombreCategoria'];
            $category->estado = true;

            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('categories', 'public');
                $category->imagen = $path;
            }

            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Categoria::findOrFail($id);

            $validated = $request->validate([
                'nombreCategoria' => 'required|string|max:255',
            ]);

            $category->nombreCategoria = $validated['nombreCategoria'];
            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus(Request $request, $id)
    {
        try {
            $category = Categoria::findOrFail($id);

            $validated = $request->validate([
                'active' => 'required|boolean',
            ]);

            $category->estado = $validated['active'];
            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category status updated successfully',
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling category status: ' . $e->getMessage()
            ], 500);
        }
    }
}

?>