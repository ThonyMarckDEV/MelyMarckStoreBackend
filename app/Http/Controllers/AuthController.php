<?php

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validar los datos de la solicitud
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Buscar el usuario por su 'email' en la tabla datos
        $user = User::whereHas('datos', function ($query) use ($request) {
            $query->where('email', $request->email);
        })->first();

        // Si el usuario no existe o la contraseña no es válida
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos',
            ], 401);
        }

        // Verificar si el estado del usuario es activo
        if ($user->estado !== 1) {
            return response()->json([
                'message' => 'Error: estado del usuario inactivo',
            ], 403);
        }

        // Generar token de acceso
        $now = time();
        $expiresIn = config('jwt.ttl') * 60;
        $rememberMe = $request->remember_me ?? false;
        $refreshTTL = $rememberMe ? 7 * 24 * 60 * 60 : 1 * 24 * 60 * 60;
        $secret = config('jwt.secret');

        // Access token payload
        $accessPayload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + $expiresIn,
            'nbf' => $now,
            'jti' => Str::random(16),
            'sub' => $user->idUsuario,
            'prv' => sha1(config('app.key')),
            'rol' => $user->rol->nombre,
            'email' => $user->datos->email,
            'email_verified' => $user->datos->email_verified,
            'nombre' => $user->datos->nombre,
            'apellido' => $user->datos->apellido,
            'idCarrito' => $user->carrito->idCarrito,
            'google_user' => $user->datos->google_user,
        ];

        // Refresh token payload
        $refreshPayload = [
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + $expiresIn,
            'nbf' => $now,
            'jti' => Str::random(16),
            'sub' => $user->idUsuario,
            'prv' => sha1(config('app.key')),
            'rol' => $user->rol->nombre,
            'email' => $user->datos->email,
            'email_verified' => $user->datos->email_verified,
            'nombre' => $user->datos->nombre,
            'apellido' => $user->datos->apellido,
            'idCarrito' => $user->carrito->idCarrito,
            'google_user' => $user->datos->google_user,
        ];

        // Generar tokens
        $accessToken = JWT::encode($accessPayload, $secret, 'HS256');
        $refreshToken = JWT::encode($refreshPayload, $secret, 'HS256');

        // Gestionar sesiones activas (máximo 3)
        $activeSessions = DB::table('refresh_tokens')
            ->where('idUsuario', $user->idUsuario)
            ->where('expires_at', '>', now())
            ->orderBy('created_at', 'asc')
            ->get();

        if ($activeSessions->count() >= 3) {
            // Eliminar la sesión más antigua
            DB::table('refresh_tokens')
                ->where('idToken', $activeSessions->first()->idToken)
                ->delete();
        }

        // Insertar nuevo token de refresco
        $refreshTokenId = DB::table('refresh_tokens')->insertGetId([
            'idUsuario' => $user->idUsuario,
            'refresh_token' => $refreshToken,
            'ip_address' => $request->ip(),
            'device' => $request->userAgent(),
            'expires_at' => date('Y-m-d H:i:s', $now + $refreshTTL),
            'created_at' => date('Y-m-d H:i:s', $now),
            'updated_at' => date('Y-m-d H:i:s', $now),
        ]);

        return response()->json([
            'message' => 'Login exitoso',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'idRefreshToken' => $refreshTokenId,
        ], 200);
    }

    // Método para refrescar el token
    public function refresh(Request $request)
    {
        // Validar el refresh token
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Refresh token inválido',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            // Verificar el token con Firebase JWT
            $secret = config('jwt.secret');
            $payload = \Firebase\JWT\JWT::decode($request->refresh_token, new \Firebase\JWT\Key($secret, 'HS256'));
            
            // Verificar que sea un token de refresco
            if (!isset($payload->type) || $payload->type !== 'refresh') {
                return response()->json([
                    'message' => 'El token proporcionado no es un token de refresco',
                ], 401);
            }
            
            // Obtener el ID de usuario
            $userId = $payload->sub;
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado',
                ], 404);
            }
            
            // Generar un nuevo token de acceso con Firebase JWT
            $now = time();
            $expiresIn = config('jwt.ttl') * 60;
            
            // Crear payload del token de acceso con custom claims del usuario
            $accessPayload = [
                'iss' => config('app.url'),
                'iat' => $now,
                'exp' => $now + $expiresIn,
                'nbf' => $now,
                'jti' => Str::random(16),
                'sub' => $user->idUsuario,
                'prv' => sha1(config('app.key')),
                'rol' => $user->rol->nombre,
                'email' => $user->datos->email,
                'email_verified' => $user->datos->email_verified,
                'nombre' => $user->datos->nombre,
                'apellido' => $user->datos->apellido,
                'idCarrito' => $user->carrito->idCarrito,
                'google_user' => $user->datos->google_user,
            ];
            
            // Generar nuevo token de acceso usando Firebase JWT
            $newToken = \Firebase\JWT\JWT::encode($accessPayload, $secret, 'HS256');
            
            return response()->json([
                'message' => 'Token actualizado',
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => $expiresIn
            ], 200);
            
        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'message' => 'Refresh token expirado'
            ], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return response()->json([
                'message' => 'Refresh token inválido'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // In your AuthController.php
    public function validateRefreshToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token_id' => 'required',
            'userID' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => 'Datos inválidos'
            ], 400);
        }

        // Buscar el token en la base de datos
        $refreshToken = DB::table('refresh_tokens')
            ->where('idToken', $request->refresh_token_id)
            ->where('idUsuario', $request->userID)
            ->first();

        // Si no existe o no coincide, significa que este token ya no es válido
        // (posiblemente porque el usuario inició sesión en otro dispositivo)
        if (!$refreshToken) {
            return response()->json([
                'valid' => false,
                'message' => 'Token no válido o expirado'
            ], 200);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Token válido'
        ], 200);
    }


    // Método para cerrar sesión
    public function logout()
    {
        try {
            // Invalidar el token actual
            JWTAuth::invalidate(JWTAuth::getToken());
            
            return response()->json([
                'message' => 'Sesión cerrada exitosamente'
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Error al cerrar sesión',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}