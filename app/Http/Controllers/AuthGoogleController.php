<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Datos;
use App\Models\User;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class AuthGoogleController extends Controller
{
   
       public function googleLogin(Request $request)
    {
        // Validar el token de Google
        $validator = Validator::make($request->all(), [
            'id_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            // Inicializar el cliente de Google
            $client = new Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($request->id_token);

            if (!$payload) {
                return response()->json([
                    'message' => 'Token de Google inválido',
                ], 401);
            }

            // Obtener datos del usuario desde el token
            $email = $payload['email'];
            $firstName = $payload['given_name'] ?? null; // Extract first name
            $lastName = $payload['family_name'] ?? null; // Extract last name

            // Buscar o crear el registro en la tabla datos
            $dato = Datos::where('email', $email)->first();
            $isNewUser = false; // Bandera para identificar si es un usuario nuevo
            if (!$dato) {
                $dato = Datos::create([
                    'nombre' => $firstName,
                    'apellido' => $lastName,
                    'email' => $email,
                    'email_verified' => 1, // Indicar que el email está verificado
                    'google_user' => 1, // Indicar que es un usuario de Google
                ]);
                $isNewUser = true; // Marcar como nuevo si no existe el correo
            }

            // Buscar o crear el usuario
            $user = User::whereHas('datos', function ($query) use ($email) {
                $query->where('email', $email);
            })->first();

            if (!$user && $isNewUser) {
                $user = User::create([
                    'password' => Hash::make(Str::random(16)),
                    'idDatos' => $dato->idDatos,
                    'idRol' => 2,
                    'estado' => 1,
                ]);

                // Crear carrito para el nuevo usuario
                Carrito::create([
                    'idUsuario' => $user->idUsuario,
                ]);
            } elseif ($user && $user->estado !== 1) {
                return response()->json([
                    'message' => 'Error: estado del usuario inactivo',
                ], 403);
            }

            // Generar tokens JWT
            $now = time();
            $expiresIn = config('jwt.ttl') * 60;
            $refreshTTL = 1 * 24 * 60 * 60;
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
                'exp' => $now + $refreshTTL,
                'nbf' => $now,
                'jti' => Str::random(16),
                'sub' => $user->idUsuario,
                'prv' => sha1(config('app.key')),
                'type' => 'refresh',
                'rol' => $user->rol->nombre,
                'email' => $user->datos->email,
                'email_verified' => $user->datos->email_verified,
                'nombre' => $user->datos->nombre,
                'apellido' => $user->datos->apellido,
                'idCarrito' => $user->carrito->idCarrito,
                'google_user' => $user->datos->google_user,
            ];

            // Generar tokens
            $accessToken = \Firebase\JWT\JWT::encode($accessPayload, $secret, 'HS256');
            $refreshToken = \Firebase\JWT\JWT::encode($refreshPayload, $secret, 'HS256');

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
                'message' => 'Login con Google exitoso',
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'idRefreshToken' => $refreshTokenId,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el login con Google',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}