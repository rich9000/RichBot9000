<?php

namespace App\Http\Controllers;

use App\Models\PhoneTree;
use App\Models\PhoneTreeNumber;
use Illuminate\Http\Request;

class PhoneTreeNumberController extends Controller
{
    public function index(Request $request, PhoneTree $phoneTree)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $numbers = $phoneTree->numbers()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $numbers
        ]);
    }

    public function store(Request $request, PhoneTree $phoneTree)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validated = $request->validate([
            'phone_number' => 'required|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $number = $phoneTree->numbers()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $number
        ], 201);
    }

    public function show(Request $request, PhoneTree $phoneTree, PhoneTreeNumber $number)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $number
        ]);
    }

    public function update(Request $request, PhoneTree $phoneTree, PhoneTreeNumber $number)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validated = $request->validate([
            'phone_number' => 'sometimes|required|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $number->update($validated);

        return response()->json([
            'success' => true,
            'data' => $number
        ]);
    }

    public function destroy(Request $request, PhoneTree $phoneTree, PhoneTreeNumber $number)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $number->delete();

        return response()->json([
            'success' => true,
            'message' => 'Number deleted successfully'
        ]);
    }
} 