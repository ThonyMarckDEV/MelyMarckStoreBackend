<?php

use App\Http\Controllers\AuthGoogleController;
use App\Http\Controllers\SessionsController;
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

// RUTAS PARA X VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:cliente'])->group(function () { 
// RUTAS PARA Sesiones
  Route::get('/sessions', [SessionsController::class, 'getActiveSessions']);
  Route::delete('/sessions', [SessionsController::class, 'deleteSession']);

});

// RUTAS PARA X VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:role2'])->group(function () { 

      
  
});


// RUTAS PARA Roles X y X ....
Route::middleware(['auth.jwt', 'checkRolesMW'])->group(function () { 

      
  
});

        


