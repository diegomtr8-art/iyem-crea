<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        return Inertia::render('Roles/Index', [
            // Cargamos los roles con sus permisos ya relacionados
            'roles' => Role::with('permissions')->get(),
            // Enviamos todos los permisos disponibles para los checkboxes
            'allPermissions' => Permission::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name]);

        // Sincronizamos los nombres de los permisos enviados desde Vue
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return back();
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        $role->update(['name' => $request->name]);
        
        // Esto actualiza la tabla intermedia automáticamente
        $role->syncPermissions($request->permissions);

        return back();
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return back();
    }
}