<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\SubCategoria;
use Illuminate\Http\Request;

class SubCategoriesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $categoryId = $request->query('category_id');

            if (!$categoryId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category ID is required'
                ], 400);
            }

            // Fetch active subcategories for the given category
            $subcategories = SubCategoria::active()
                ->where('idCategoria', $categoryId)
                ->select('idSubCategoria', 'nombreSubCategoria', 'idCategoria')
                ->get();

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
}