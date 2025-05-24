<?php

use App\Http\Controllers\AuthGoogleController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\DetalleCarritoController;
use App\Http\Controllers\DirectionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\SubCategoriesController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas (no requieren autenticación)
Route::post('/login', [AuthController::class, 'login']);

Route::post('/google-login', [AuthGoogleController::class, 'googleLogin']);

Route::post('/refresh', [AuthController::class, 'refresh']);

Route::post('/validate-refresh-token', [AuthController::class, 'validateRefreshToken']);

Route::post('/usuarios', [UserController::class, 'store']);

// Rutas publicas para home
Route::get('/categories', [CategoriesController::class, 'index']);
Route::get('/subcategories', [SubCategoriesController::class, 'index']);
Route::get('/new-products', [ProductController::class, 'getNewProducts']);


// Rutas publicas para products
Route::get('/products', [ProductController::class, 'index']);


// RUTAS PARA X VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:cliente'])->group(function () { 
  // RUTAS PARA Sesiones
  Route::get('/sessions', [SessionsController::class, 'getActiveSessions']);
  Route::delete('/sessions', [SessionsController::class, 'deleteSession']);

  //RUTA PARA CARRITO
  Route::get('/carrito/cantidad/{idCarrito}', [CarritoController::class, 'getCartItemCount']);
  Route::post('/carrito/detalles', [CarritoController::class, 'addToCarrito']);

  //RUTA PARA DETALLE CARRITO
  Route::get('/cart/{idCarrito}/details', [DetalleCarritoController::class, 'index']);
  Route::post('/cart/{idCarrito}/details', [DetalleCarritoController::class, 'store']);
  Route::put('/cart/details/{idDetalle}', [DetalleCarritoController::class, 'update']);
  Route::delete('/cart/details/{idDetalle}', [DetalleCarritoController::class, 'destroy']);

  //RUTAS PARA DIRECCIONES
  Route::get('/directions', [DirectionController::class, 'index']);
  Route::post('/directions', [DirectionController::class, 'store']);
  Route::put('/directions/{id}', [DirectionController::class, 'update']);
  Route::delete('/directions/{id}', [DirectionController::class, 'destroy']);
  Route::patch('/directions/{id}/select', [DirectionController::class, 'select']);

});

// RUTAS PARA X VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:role2'])->group(function () { 

      
  
});


// RUTAS PARA Roles X y X ....
Route::middleware(['auth.jwt', 'checkRolesMW'])->group(function () { 

      
  
});

        


