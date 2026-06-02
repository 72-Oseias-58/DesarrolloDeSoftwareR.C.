<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function index()
    {
        $sucursales = Sucursal::orderBy('id_sucursal', 'desc')->get();

        return response()->json([
            'sucursales' => $sucursales,
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100|unique:sucursales,nombre',
            'direccion' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:30',
        ]);

        $sucursal = Sucursal::create([
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'] ?? null,
            'telefono' => $datos['telefono'] ?? null,
            'estado' => 'ACTIVA',
        ]);

        return response()->json([
            'message' => 'Sucursal creada correctamente',
            'sucursal' => $sucursal,
        ], 201);
    }

    public function show($id)
    {
        $sucursal = Sucursal::findOrFail($id);

        return response()->json([
            'sucursal' => $sucursal,
        ]);
    }

    public function update(Request $request, $id)
    {
        $sucursal = Sucursal::findOrFail($id);

        $datos = $request->validate([
            'nombre' => 'required|string|max:100|unique:sucursales,nombre,' . $id . ',id_sucursal',
            'direccion' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:30',
        ]);

        $sucursal->update($datos);

        return response()->json([
            'message' => 'Sucursal actualizada correctamente',
            'sucursal' => $sucursal,
        ]);
    }

    public function cambiarEstado(Request $request, $id)
    {
        $sucursal = Sucursal::findOrFail($id);

        $datos = $request->validate([
            'estado' => 'required|in:ACTIVA,INACTIVA',
        ]);

        $sucursal->update([
            'estado' => $datos['estado'],
        ]);

        return response()->json([
            'message' => 'Estado de sucursal actualizado correctamente',
            'sucursal' => $sucursal,
        ]);
    }
}