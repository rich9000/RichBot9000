<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $roles = Role::all();
        return response()->json($roles, 200);
    }

    /**
     * Update the specified user's roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserRoles(Request $request, $userId)
    {
        $user = User::findOrFail($userId);
        $roles = $request->input('roles', []);

        // Sync roles with the user
        $user->roles()->sync($roles);

        return response()->json(['message' => 'Roles updated successfully.'], 200);
    }
}
