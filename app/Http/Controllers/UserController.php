<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        // Traemos usuarios con sus roles y todos los roles disponibles
        return Inertia::render('Users/Index', [
            'users' => User::with('roles')->get(),
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|exists:roles,name',
        ]);

        // 1. Crear el usuario (cuentas operativas creadas por un admin: se dan
        // por verificadas, ya que no pasan por el flujo de auto-registro).
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        // 2. Asignarle el rol de Spatie
        $user->assignRole($request->role);

        return redirect()->back()->with('success', 'Usuario creado correctamente.');
    }
    

public function update(Request $request, User $user)
{
    $request->validate([
        'name' => 'required|string|max:255',
        // Asegúrate de ignorar el email del usuario actual al validar unique
        'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        'password' => 'nullable|string|min:8', // 'nullable' es clave aquí
        'role' => 'required|string|exists:roles,name',
    ]);

    $data = [
        'name' => $request->name,
        'email' => $request->email,
    ];

    // Solo actualizamos la contraseña si el campo no está vacío
    if (!empty($request->password)) {
        $data['password'] = Hash::make($request->password);
    }

    $user->update($data);

    // Sincronizamos el rol con Spatie (esto reemplaza el rol anterior)
    $user->syncRoles($request->role);

    return redirect()->back()->with('success', 'Usuario actualizado correctamente.');
}

public function destroy(User $user)
{
    // Opcional: Impedir que el super-administrador se borre a sí mismo
    if (auth()->id() === $user->id) {
        return redirect()->back()->with('error', 'No puedes eliminarte a ti mismo.');
    }

    $user->delete();
    return redirect()->back()->with('success', 'Usuario eliminado correctamente.');
}

}
