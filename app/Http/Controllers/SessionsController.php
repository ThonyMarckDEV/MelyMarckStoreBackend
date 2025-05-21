<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SessionsController extends Controller
{
    public function getActiveSessions(Request $request)
    {
        $userId = auth()->id();

        $sessions = DB::table('refresh_tokens')
            ->where('idUsuario', $userId)
            ->where('expires_at', '>', now())
            ->select('idToken', 'ip_address', 'device', 'created_at', 'expires_at')
            ->get()
            ->map(function ($session) use ($request) {
                return [
                    'idRefreshToken' => $session->idToken,
                    'ip_address' => $session->ip_address,
                    'device' => $this->parseDevice($session->device),
                    'created_at' => $session->created_at,
                    'expires_at' => $session->expires_at,
                    'is_current' => $session->idToken == $request->header('X-Refresh-Token-ID'),
                ];
            });

        return response()->json([
            'message' => 'Sesiones activas obtenidas',
            'sessions' => $sessions,
        ], 200);
    }

    public function deleteSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idRefreshToken' => 'required|exists:refresh_tokens,idToken',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        $userId = auth()->id();
        $idRefreshToken = $request->idRefreshToken;

        $session = DB::table('refresh_tokens')
            ->where('idToken', $idRefreshToken)
            ->where('idUsuario', $userId)
            ->first();

        if (!$session) {
            return response()->json([
                'message' => 'Sesión no encontrada o no pertenece al usuario',
            ], 403);
        }

        DB::table('refresh_tokens')
            ->where('idToken', $idRefreshToken)
            ->delete();

        $isCurrentSession = $idRefreshToken == $request->header('X-Refresh-Token-ID');

        return response()->json([
            'message' => 'Sesión eliminada correctamente',
            'is_current_session' => $isCurrentSession,
        ], 200);
    }

    protected function parseDevice($userAgent)
    {
        if (stripos($userAgent, 'mobile') !== false) {
            return 'Dispositivo Móvil';
        } elseif (stripos($userAgent, 'tablet') !== false) {
            return 'Tablet';
        } elseif (stripos($userAgent, 'windows') !== false) {
            return 'Windows PC';
        } elseif (stripos($userAgent, 'macintosh') !== false) {
            return 'Mac';
        } else {
            return 'Desconocido';
        }
    }
}