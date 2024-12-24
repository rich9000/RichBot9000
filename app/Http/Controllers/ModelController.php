<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AiModel;

class ModelController extends Controller
{
    // Display a listing of the models
    public function index()
    {
        $models = AiModel::all();

        return response()->json($models);
    }

    // Store a newly created model
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|max:32',
            'name' => 'required|string|max:128',
        ]);

        $model = AiModel::create($validated);

        return response()->json($model, 201); // HTTP status code 201: Created
    }

    // Show a specific model
    public function show($id)
    {
        $model = AiModel::findOrFail($id);

        return response()->json($model);
    }

    // Update a specific model
    public function update(Request $request, $id)
    {
        $model = AiModel::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|string|max:32',
            'name' => 'required|string|max:128',
        ]);

        $model->update($validated);

        return response()->json($model);
    }

    // Delete a specific model
    public function destroy($id)
    {
        $model = AiModel::findOrFail($id);
        $model->delete();

        return response()->json(['message' => 'Model deleted successfully']);
    }
}
