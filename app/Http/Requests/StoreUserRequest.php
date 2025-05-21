<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Validaciones para usuario
            'username' => 'required|string|max:50|unique:usuarios,username',
            'password' => 'required|string|min:6',
            'idRol' => 'required|exists:roles,idRol',
            'estado' => 'required|boolean',
    
            // Validaciones para datos personales
            // 'nombre' => 'required|string|max:100',
            // 'apellido' => 'required|string|max:100',
            // 'email' => 'required|email|unique:datos,email',
            // 'direccion' => 'nullable|string|max:255',
            // 'dni' => 'required|string|size:8|unique:datos,dni',
            // 'ruc' => 'nullable|string|size:11|unique:datos,ruc',
            // 'telefono' => 'required|string|max:15',
        ];
    }
    
}
