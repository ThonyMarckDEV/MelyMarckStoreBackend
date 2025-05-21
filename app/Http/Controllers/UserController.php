<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDatosRequest;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Datos;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Muestra una lista de todos los usuarios con sus relaciones de rol y datos personales.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return User::with(['rol', 'datos'])->get();
    }

    /**
     * Almacena un nuevo usuario junto con sus datos personales en la base de datos.
     *
     * Este método valida los datos usando StoreUserRequest, crea primero los datos personales
     * en la tabla 'datos' y luego crea el usuario asociado en la tabla 'usuarios'.
     * La contraseña se encripta antes de guardarse.
     * Todo el proceso se ejecuta dentro de una transacción para asegurar la integridad.
     *
     * @param  \App\Http\Requests\StoreUserRequest  $request
     * @return \App\Models\User
     */
    public function store(StoreUserRequest $request)
    {
        return DB::transaction(function () use ($request) {
    
            $datosValidator = Validator::make(
                $request->all(),
                (new StoreDatosRequest())->rules()
            );
    
            $datosValidator->validate();
    
            $datos = Datos::create($request->only([
                'nombre', 'apellido', 'email', 'direccion', 'dni', 'ruc', 'telefono'
            ]));
    
            $dataUsuario = $request->only([
                'username', 'password', 'idRol', 'estado'
            ]);
            $dataUsuario['idDatos'] = $datos->idDatos;
            $dataUsuario['password'] = bcrypt($dataUsuario['password']);
    
            $usuario = User::create($dataUsuario);
    
            return response()->json([
                'message' => 'Usuario creado exitosamente',
            ], 201);
        });
    }
    

    /**
     * Muestra un usuario específico junto con sus relaciones de rol y datos personales.
     *
     * @param  \App\Models\User  $user
     * @return \App\Models\User
     */
    public function show(User $user)
    {
        return $user->load(['rol', 'datos']);
    }

    /**
     * Actualiza la información de un usuario específico.
     * Si se incluye contraseña, se encripta. Si no, se mantiene la actual.
     *
     * @param  \App\Http\Requests\UpdateUserRequest  $request
     * @param  \App\Models\User  $user
     * @return \App\Models\User
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return $user;
    }

    /**
     * Elimina un usuario de la base de datos.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado'], 200);
    }

    /**
     * Cambia el estado de un usuario específico.
     * Alterna entre activo e inactivo.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function cambiarEstado(User $user, UserService $userService)
    {
        $usuarioActualizado = $userService->cambiarEstado($user);

        return response()->json([
            'message' => 'Estado actualizado',
            'estado' => $usuarioActualizado->estado
        ]);
    }
}
