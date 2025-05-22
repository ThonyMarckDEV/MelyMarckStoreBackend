<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index()
    {
        try {
            $categories = Categoria::active()
                ->select('idCategoria', 'nombreCategoria' , 'imagen')
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
}