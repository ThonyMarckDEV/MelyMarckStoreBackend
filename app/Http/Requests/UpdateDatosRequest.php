<?php

// app/Http/Requests/UpdateDatosRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDatosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'sometimes|required|string|max:100',
            'apellido' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:datos,email,' . $this->route('dato')->idDatos . ',idDatos',
            'direccion' => 'nullable|string|max:255',
            'dni' => 'sometimes|required|string|max:15|unique:datos,dni,' . $this->route('dato')->idDatos . ',idDatos',
            'ruc' => 'nullable|string|max:20',
            'telefono' => 'sometimes|required|string|max:20',
        ];
    }
}
