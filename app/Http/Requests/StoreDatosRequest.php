<?php

// app/Http/Requests/StoreDatosRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDatosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'email' => 'required|email|unique:datos,email',
            'direccion' => 'nullable|string|max:255',
            'dni' => 'required|string|max:15|unique:datos,dni',
            'ruc' => 'nullable|string|max:20',
            'telefono' => 'required|string|max:20',
        ];
    }
}
