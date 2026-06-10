<?php

namespace App\Http\Controllers;

use App\Models\Interesado;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

class InteresadoController extends Controller
{
    /**
     * Listado con filtros avanzados y búsqueda optimizada.
     */
    public function index(Request $request)
    {
        return Inertia::render('Interesados/Index', [
            'interesados' => Interesado::query()
                ->when($request->search, function ($query, $search) {
                    $query->where(function($q) use ($search) {
                        $q->where('nombre_ciudadano', 'like', "%{$search}%")
                          ->orWhere('empresa', 'like', "%{$search}%")
                          ->orWhere('telefono', 'like', "%{$search}%");
                    });
                })
                ->when($request->municipio, fn($q, $m) => $q->where('municipio', 'like', "%{$m}%"))
                ->when($request->modalidad, fn($q, $mod) => $q->where('modalidad', $mod))
                ->when($request->sexo, fn($q, $s) => $q->where('sexo', $s))
                ->when($request->seguimiento, fn($q, $seg) => $q->where('seguimiento', 'like', "%{$seg}%"))
                ->when($request->estatus, fn($q, $e) => $q->where('estatus', $e))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'filters' => $request->only(['search', 'municipio', 'modalidad', 'sexo', 'seguimiento', 'estatus'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Interesados/Create');
    }

    /**
     * Registro inicial de prospectos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'medio_ingreso' => 'required|string',
            'nombre_ciudadano' => 'required|string|max:255',
            'empresa' => 'nullable|string|max:255',
            'correo_electronico' => 'nullable|email|unique:interesados,correo_electronico',
            'telefono' => 'required|string|max:15|unique:interesados,telefono',
            'sexo' => 'required|in:Hombre,Mujer,Otro',
            'municipio' => 'required|string',
            'fecha_nacimiento' => 'nullable|date',
            'mayahablante' => 'boolean',
            'discapacidad' => 'boolean',
            'giro_comercial' => 'nullable|string',
            'alta_sat' => 'boolean',
            'destino_credito' => 'nullable|string',
            'modalidad' => 'required|string',
            'atendio' => 'required|string',
            'seguimiento' => 'required|string',
        ], [
            'telefono.unique' => 'Este número de teléfono ya está registrado.',
            'correo_electronico.unique' => 'Este correo ya existe en nuestra base de datos.'
        ]);

        Interesado::create($validated);

        return Redirect::route('interesados.index')->with('success', '¡Ciudadano registrado correctamente!');
    }

    /**
     * Bloqueo de edición para registros ya convertidos.
     */
    public function edit(Interesado $interesado)
    {
        if ($interesado->estatus === 'Convertido') {
            return Redirect::route('interesados.index')
                ->with('error', 'Acceso denegado: El registro ya es un Acreditado y no puede editarse desde este módulo.');
        }

        return Inertia::render('Interesados/Edit', [
            'interesado' => $interesado
        ]);
    }

    /**
     * Actualización con validación de seguridad.
     */
    public function update(Request $request, Interesado $interesado)
    {
        // Candado preventivo de backend
        if ($interesado->estatus === 'Convertido') {
            return Redirect::route('interesados.index')
                ->with('error', 'Operación no permitida: Registro bloqueado por conversión.');
        }

        $validated = $request->validate([
            'nombre_ciudadano' => 'required|string|max:255',
            'telefono' => ['required', 'string', Rule::unique('interesados')->ignore($interesado->id)],
            'correo_electronico' => ['nullable', 'email', Rule::unique('interesados')->ignore($interesado->id)],
            'medio_ingreso' => 'required',
            'modalidad' => 'required',
            'municipio' => 'required',
            'sexo' => 'required',
            'fecha_nacimiento' => 'nullable|date',
            'estatus' => 'required|string',
            'atendio' => 'required',
            'seguimiento' => 'required',
            'mayahablante' => 'boolean',
            'discapacidad' => 'boolean',
            'alta_sat' => 'boolean',
            'destino_credito' => 'nullable|string',
            'empresa' => 'nullable|string',
            'giro_comercial' => 'nullable|string',
        ]);

        $interesado->update($validated);

        return Redirect::route('interesados.index')->with('success', 'Información actualizada con éxito.');
    }

    /**
     * Eliminación solo si no está convertido.
     */
    public function destroy(Interesado $interesado)
    {
        if ($interesado->estatus === 'Convertido') {
            return Redirect::route('interesados.index')
                ->with('error', 'No se puede eliminar un registro que ya ha sido convertido a Acreditado.');
        }

        $interesado->delete();
        return Redirect::route('interesados.index')->with('success', 'El registro ha sido eliminado.');
    }

    /**
     * Proceso de conversión a Acreditado (Inicia flujo de crédito).
     */
    public function convertir($id)
    {
        $interesado = Interesado::findOrFail($id);

        // Si ya es cliente, evitamos que vuelva a entrar al flujo de creación
        if ($interesado->estatus === 'Convertido') {
            return Redirect::route('interesados.index')
                ->with('error', 'Este ciudadano ya cuenta con un expediente de crédito activo.');
        }

        // Cambiamos estatus a 'Aprobado' para indicar que está en proceso de firma/registro
        $interesado->update(['estatus' => 'Aprobado']);

        return Redirect::route('acreditados.create', [
            'interesado_id' => $interesado->id,
            'nombre'        => $interesado->nombre_ciudadano,
            'municipio'     => $interesado->municipio,
            'modalidad'     => $interesado->modalidad,
            'sexo'          => in_array($interesado->sexo, ['Hombre', 'H']) ? 'H' : 'M',
            'correo'        => $interesado->correo_electronico,
        ]);
    }
}