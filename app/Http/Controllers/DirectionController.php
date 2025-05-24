<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\DetalleDireccion;
use Illuminate\Support\Facades\Auth;

class DirectionController extends Controller
{
    /**
     * Obtener todas las direcciones del usuario autenticado
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $directions = DetalleDireccion::where('idUsuario', $user->idUsuario)
                ->get();

            return response()->json([
                'message' => 'Direcciones obtenidas correctamente',
                'directions' => $directions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las direcciones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una nueva dirección
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'departamento' => 'required|string',
            'provincia' => 'required|string',
            'distrito' => 'required|string',
            'direccion_shalom' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            // Si la nueva dirección se marca como activa, desactivar otras
            if ($request->estado) {
                DetalleDireccion::where('idUsuario', $user->idUsuario)
                    ->where('estado', 1)
                    ->update(['estado' => 0]);
            }

            $direction = DetalleDireccion::create([
                'idUsuario' => $user->idUsuario,
                'departamento' => $request->departamento,
                'provincia' => $request->provincia,
                'distrito' => $request->distrito,
                'direccion_shalom' => $request->direccion_shalom,
                'estado' => $request->estado ?? 1,
            ]);

            return response()->json([
                'message' => 'Dirección creada correctamente',
                'direction' => $direction,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la dirección',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar una dirección existente
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'departamento' => 'required|string',
            'provincia' => 'required|string',
            'distrito' => 'required|string',
            'direccion_shalom' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Datos inválidos',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $direction = DetalleDireccion::where('idUsuario', $user->idUsuario)
                ->where('idDireccion', $id)
                ->first();

            if (!$direction) {
                return response()->json([
                    'message' => 'Dirección no encontrada',
                ], 404);
            }

            // Si la dirección se marca como activa, desactivar otras
            if ($request->estado) {
                DetalleDireccion::where('idUsuario', $user->idUsuario)
                    ->where('estado', 1)
                    ->update(['estado' => 0]);
            }

            $direction->update([
                'departamento' => $request->departamento,
                'provincia' => $request->provincia,
                'distrito' => $request->distrito,
                'direccion_shalom' => $request->direccion_shalom,
                'estado' => $request->estado ?? $direction->estado,
            ]);

            return response()->json([
                'message' => 'Dirección actualizada correctamente',
                'direction' => $direction,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la dirección',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar una dirección
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $direction = DetalleDireccion::where('idUsuario', $user->idUsuario)
                ->where('idDireccion', $id)
                ->first();

            if (!$direction) {
                return response()->json([
                    'message' => 'Dirección no encontrada',
                ], 404);
            }

            $direction->delete();

            return response()->json([
                'message' => 'Dirección eliminada correctamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la dirección',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Seleccionar una dirección como activa (estado = 1)
     */
    public function select($id)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $direction = DetalleDireccion::where('idUsuario', $user->idUsuario)
                ->where('idDireccion', $id)
                ->first();

            if (!$direction) {
                return response()->json([
                    'message' => 'Dirección no encontrada',
                ], 404);
            }

            // Desactivar todas las demás direcciones
            DetalleDireccion::where('idUsuario', $user->idUsuario)
                ->where('estado', 1)
                ->update(['estado' => 0]);

            // Activar la dirección seleccionada
            $direction->update(['estado' => 1]);

            return response()->json([
                'message' => 'Dirección seleccionada correctamente',
                'direction' => $direction,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al seleccionar la dirección',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}