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
                'nombreCategoria' => 'required|string|max:255', // Changed from nameType
                'fileImage' => 'nullable|image|mimes:jpeg,jpg,gif|max:2048',
            ]);

            $category = new Categoria();
            $category->nombreCategoria = $validated['nombreCategoria'];
            $category->estado = true;
            $category->save();

            if ($request->hasFile('fileImage')) {
                $filename = $request->file('fileImage')->getClientOriginalName();
                $path = $request->file('fileImage')->storeAs("categories/{$category->idCategoria}", $filename, 'public');
                $category->imagen = $path; // Changed from fileImage
                $category->save();
            }

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

            Log::info('Update category name request', [
                'id' => $id,
                'has_nombreCategoria' => $request->has('nombreCategoria'),
                'nombreCategoria_value' => $request->input('nombreCategoria'),
                'current_name' => $category->nombreCategoria,
            ]);

            $validated = $request->validate([
                'nombreCategoria' => 'required|string|max:255', // Changed from nameType
            ]);

            $hasChanges = false;

            $newName = trim($request->input('nombreCategoria'));
            if (!empty($newName) && $newName !== $category->nombreCategoria) {
                $category->nombreCategoria = $newName;
                $hasChanges = true;
                Log::info('Name will be updated', ['old' => $category->nombreCategoria, 'new' => $newName]);
            }

            if (!$hasChanges) {
                Log::info('No valid name changes detected', [
                    'has_nombreCategoria' => $request->has('nombreCategoria'),
                ]);
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

            Log::info('Update category image request', [
                'id' => $id,
                'all' => $request->all(),
                'files' => $request->files->all(),
                'headers' => $request->headers->all(),
                'content_type' => $request->header('Content-Type'),
            ]);

            Log::info('Image upload details', [
                'id' => $id,
                'has_fileImage' => $request->hasFile('fileImage'),
                'image_details' => $request->hasFile('fileImage') ? [
                    'name' => $request->file('fileImage')->getClientOriginalName(),
                    'type' => $request->file('fileImage')->getMimeType(),
                    'size' => $request->file('fileImage')->getSize() / 1024 . 'KB',
                    'valid' => $request->file('fileImage')->isValid(),
                ] : 'No image',
            ]);

            $validated = $request->validate([
                'fileImage' => 'required|image|mimes:jpeg,jpg,gif|max:2048',
            ]);

            if ($request->hasFile('fileImage') && $request->file('fileImage')->isValid()) {
                if ($category->imagen) { // Changed from fileImage
                    Storage::disk('public')->delete($category->imagen);
                }
                $filename = $request->file('fileImage')->getClientOriginalName();
                $path = $request->file('fileImage')->storeAs("categories/{$category->idCategoria}", $filename, 'public');
                $category->imagen = $path; // Changed from fileImage
                $category->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Category image updated successfully',
                    'data' => $category
                ], 200);
            }

            Log::warning('No valid image provided');
            return response()->json([
                'success' => false,
                'message' => 'No valid image provided. Please select a JPEG, JPG, or GIF.'
            ], 400);
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

            $category->estado = $validated['active']; // Changed from state
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
