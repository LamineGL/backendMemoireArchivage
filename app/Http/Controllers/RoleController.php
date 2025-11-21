<?php

namespace App\Http\Controllers;

use App\Models\Role;

class RoleController extends Controller
{
    /**
     * Liste de tous les rôles
     * GET /api/roles
     */
    public function index()
    {
        $roles = Role::withCount('users')->get();
        return response()->json($roles, 200);
    }
}
