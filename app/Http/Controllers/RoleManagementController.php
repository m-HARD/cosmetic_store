<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleManagementController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Role::query()->with('permissions')->get());
    }

    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $payload = $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->permissions()->sync($payload['permission_ids']);

        return response()->json($role->load('permissions'));
    }
}
