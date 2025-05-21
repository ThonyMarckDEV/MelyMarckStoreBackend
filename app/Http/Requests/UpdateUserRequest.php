<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
            'username' => 'sometimes|required|string|max:50|unique:usuarios,username,' . $this->user->idUsuario . ',idUsuario',
            'password' => 'nullable|string|min:6',
            'idDatos' => 'sometimes|exists:datos,idDatos',
            'idRol' => 'sometimes|exists:roles,idRol',
            'estado' => 'sometimes|boolean',
        ];
    }
}
