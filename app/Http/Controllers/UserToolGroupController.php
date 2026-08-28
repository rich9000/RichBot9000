<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ToolGroup;
use Illuminate\Http\Request;

class UserToolGroupController extends Controller
{
    /**
     * Get all tool groups for a user
     *
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserToolGroups(User $user)
    {
        $toolGroups = ToolGroup::all();
        $userToolGroups = $user->toolGroups->pluck('id')->toArray();

        return response()->json([
            'tool_groups' => $toolGroups,
            'user_tool_groups' => $userToolGroups
        ]);
    }

    /**
     * Update tool groups for a user
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserToolGroups(Request $request, User $user)
    {
        $request->validate([
            'tool_groups' => 'required|array',
            'tool_groups.*' => 'exists:tool_groups,id'
        ]);

        $user->toolGroups()->sync($request->tool_groups);

        return response()->json([
            'message' => 'Tool groups updated successfully',
            'user' => $user->load('toolGroups')
        ]);
    }
} 