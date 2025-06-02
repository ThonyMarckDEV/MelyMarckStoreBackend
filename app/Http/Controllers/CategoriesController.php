<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CategoriesController extends Controller
{
    public function index()
    {
        try {
            $categories = Categoria::where('estado', true)
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
                'message' => 'Error fetching categories: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombreCategoria' => 'required|string|max:255',
                'useImageUrl' => 'required|in:0,1,true,false', // Accept string values for boolean
                'imageUrl' => 'nullable|url|required_if:useImageUrl,1,true',
                'imagen' => 'nullable|image|mimes:jpeg,jpg,gif|max:2048|required_if:useImageUrl,0,false',
            ]);

            $category = new Categoria();
            $category->nombreCategoria = $validated['nombreCategoria'];
            $category->estado = true;

            // Convert useImageUrl to boolean
            $useImageUrl = in_array($validated['useImageUrl'], ['1', 'true'], true);

            if ($useImageUrl && $request->has('imageUrl')) {
                $category->imagen = $validated['imageUrl'];
            } elseif ($request->hasFile('imagen')) {
                // Save after generating idCategoria
                $category->save();
                $filename = $request->file('imagen')->getClientOriginalName();
                $path = $request->file('imagen')->storeAs("categories/{$category->idCategoria}", $filename, 'public');
                $category->imagen = $path;
            }

            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating category', ['error' => $e->getMessage()]);
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

            $hasChanges = false;
            $newName = trim($request->input('nombreCategoria'));
            if (!empty($newName) && $newName !== $category->nombreCategoria) {
                $category->nombreCategoria = $newName;
                $hasChanges = true;
                Log::info('Name will be updated', ['old' => $category->nombreCategoria, 'new' => $newName]);
            }

            if (!$hasChanges) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid changes provided. Please update the category name.'
                ], 400);
            }

            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category name updated successfully',
                'data' => $category
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating category name', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating category name: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateImage(Request $request, $id)
    {
        try {
            $category = Categoria::findOrFail($id);

            $validated = $request->validate([
                'useImageUrl' => 'required|in:0,1,true,false',
                'imageUrl' => 'nullable|url|required_if:useImageUrl,1,true',
                'fileImage' => 'nullable|image|mimes:jpeg,jpg,gif|max:2048|required_if:useImageUrl,0,false',
            ]);

            // Delete existing image if it exists and is a file (not a URL)
            if ($category->imagen && !filter_var($category->imagen, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($category->imagen);
            }

            // Convert useImageUrl to boolean
            $useImageUrl = in_array($validated['useImageUrl'], ['1', 'true'], true);

            if ($useImageUrl && $request->has('imageUrl')) {
                $category->imagen = $validated['imageUrl'];
            } elseif ($request->hasFile('fileImage') && $request->file('fileImage')->isValid()) {
                $filename = $request->file('fileImage')->getClientOriginalName();
                $path = $request->file('fileImage')->storeAs("categories/{$category->idCategoria}", $filename, 'public');
                $category->imagen = $path;
            } else {
                $category->imagen = null; // Clear image if neither provided
            }

            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category image updated successfully',
                'data' => $category
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating category image', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating category image: ' . $e->getMessage()
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