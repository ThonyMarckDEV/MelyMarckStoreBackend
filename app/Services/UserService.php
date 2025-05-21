<?php

namespace App\Services;

use App\Models\User;

class UserService
{
    public function cambiarEstado(User $user): User
    {
        $user->estado = !$user->estado;
        $user->save();
        return $user;
    }
}
