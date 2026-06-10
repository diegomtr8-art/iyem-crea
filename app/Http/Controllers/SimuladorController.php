<?php

namespace App\Http\Controllers;

use App\Models\ModalidadCrea;
use Inertia\Inertia;

class SimuladorController extends Controller
{
    public function index()
    {
        $modalidades = ModalidadCrea::orderBy('nombre')->get(['id', 'nombre']);

        return Inertia::render('Simulador/Index', [
            'modalidades' => $modalidades,
        ]);
    }
}
