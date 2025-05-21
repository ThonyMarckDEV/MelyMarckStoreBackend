<?php

namespace App\Http\Controllers;

use App\Models\Datos;
use App\Http\Requests\StoreDatosRequest;
use App\Http\Requests\UpdateDatosRequest;

class DatosController extends Controller
{
    public function index()
    {
        return Datos::all();
    }

    public function store(StoreDatosRequest $request)
    {
        return Datos::create($request->validated());
    }

    public function show(Datos $dato)
    {
        return $dato;
    }

    public function update(UpdateDatosRequest $request, Datos $dato)
    {
        $dato->update($request->validated());
        return $dato;
    }

    public function destroy(Datos $dato)
    {
        $dato->delete();
        return response()->json(['message' => 'Dato eliminado correctamente']);
    }
}
