<?php

namespace App\Http\Controllers;

use App\Models\Stage;
use App\Models\Pipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StageController extends Controller
{
    public function show($pipelineId, $stageId)
    {
        $stage = Stage::where('pipeline_id', $pipelineId)->findOrFail($stageId);
        return response()->json($stage->load('assistants', 'successTool', 'files', 'tools'));
    }

    public function store(Request $request, $pipelineId)
    {
        $validatedData = $request->validate([
            'type' => 'required|string|max:255',
            'name' => 'nullable|string|max:64',
            'assistant_ids' => 'nullable|array',
            'assistant_ids.*' => 'exists:assistants,id',
            'success_tool_id' => 'nullable|exists:tools,id',
            'order' => 'nullable|integer',
            'file_paths' => 'nullable|array',
            'file_paths.*.file_path' => 'required|string|max:255',
            'file_paths.*.file_type' => 'nullable|string|max:50',
            'file_paths.*.description' => 'nullable|string',
            'tools' => 'nullable|array',
            'tools.*.tool_id' => 'required|exists:tools,id',
            'tools.*.success_stage_id' => 'nullable|exists:stages,id',
        ]);

        $pipeline = Pipeline::findOrFail($pipelineId);
        $stage = $pipeline->stages()->create($validatedData);

        if ($request->has('assistant_ids')) {
            $stage->assistants()->sync($validatedData['assistant_ids']);
        }

        if ($request->has('file_paths')) {
            $fileData = [];
            foreach ($validatedData['file_paths'] as $file) {
                $fileData[] = [
                    'file_path' => $file['file_path'],
                    'file_type' => $file['file_type'] ?? null,
                    'description' => $file['description'] ?? null,
                ];
            }

            dd($fileData);

            $stage->files()->createMany($fileData);
        }

        if ($request->has('tools')) {
            $toolData = [];
            foreach ($validatedData['tools'] as $tool) {
                $toolData[] = [
                    'tool_id' => $tool['tool_id'],
                    'success_stage_id' => $tool['success_stage_id'] ?? null,
                ];
            }
            $stage->tools()->createMany($toolData);
        }

        return response()->json($stage->load('assistants', 'successTool', 'files', 'tools'), 201);
    }

    public function update(Request $request, $stageId)
    {
        Log::info($stageId);
        if (!$request->expectsJson()) {
            return new JsonResponse(['error' => 'Unauthorized or invalid data'], 403);
        }

        $validatedData = $request->validate([
            'type' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:64',
            'assistants' => 'nullable|array',
            'assistants.*.assistant_id' => 'required|exists:assistants,id',
            'assistants.*.order' => 'required|integer',
            'assistants.*.success_stage_id' => 'nullable|exists:stages,id',
            'assistants.*.success_tool_id' => 'nullable|exists:tools,id',
            'success_tool_id' => 'nullable|exists:tools,id',
            'order' => 'nullable|integer',
            'file_paths' => 'nullable|array',
            'file_paths.*.id' => 'nullable|exists:stage_files,id',
            'file_paths.*.file_path' => 'required|string|max:255',
            'file_paths.*.file_type' => 'nullable|string|max:50',
            'file_paths.*.description' => 'nullable|string',
            'tools' => 'nullable|array',
            'tools.*.tool_id' => 'required|exists:tools,id',
            'tools.*.success_stage_id' => 'nullable|exists:stages,id',
        ]);

        $stage = Stage::findOrFail($stageId);

        // Update stage basic data
        $stageData = $request->only(['type', 'name', 'success_tool_id', 'order']);
        $stage->update(array_filter($stageData));

        // Sync assistants with pivot data
        if ($request->has('assistants')) {
            $assistantSyncData = [];
            foreach ($validatedData['assistants'] as $assistantData) {
                $assistantSyncData[$assistantData['assistant_id']] = [
                    'order' => $assistantData['order'],
                    'success_stage_id' => $assistantData['success_stage_id'] ?? null,
                    'success_tool_id' => $assistantData['success_tool_id'] ?? null,
                ];
            }
            $stage->assistants()->sync($assistantSyncData);
        }

        // Sync or update files
        if ($request->has('file_paths')) {
            $fileData = [];
            foreach ($validatedData['file_paths'] as $file) {
                if (isset($file['id'])) {
                    $stage->files()->where('id', $file['id'])->update([
                        'file_path' => $file['file_path'],
                        'file_type' => $file['file_type'] ?? null,
                        'description' => $file['description'] ?? null,
                    ]);
                } else {
                    $fileData[] = [
                        'file_path' => $file['file_path'],
                        'file_type' => $file['file_type'] ?? null,
                        'description' => $file['description'] ?? null,
                    ];
                }
            }
            if (!empty($fileData)) {
                $stage->files()->createMany($fileData);
            }
        }

        // Sync tools
        if ($request->has('tools')) {
            $toolData = [];
            foreach ($validatedData['tools'] as $tool) {
                $toolData[] = [
                    'tool_id' => $tool['tool_id'],
                    'success_stage_id' => $tool['success_stage_id'] ?? null,
                ];
            }
            $stage->tools()->delete(); // Clear existing tools
            $stage->tools()->createMany($toolData);
        }

        return response()->json($stage->load([
            'assistants' => function ($query) {
                $query->withPivot('order', 'success_stage_id', 'success_tool_id');
            },
            'successTool', 'files', 'tools'
        ]));
    }

    public function destroy($pipelineId, $stageId)
    {
        $stage = Stage::where('pipeline_id', $pipelineId)->findOrFail($stageId);
        $stage->delete();
        return response()->json(['message' => 'Stage deleted successfully']);
    }

    public function deleteFileFromStage($stageId, $fileId)
    {
        $stage = Stage::findOrFail($stageId);

        // Check if the file exists in the stage
        $file = $stage->files()->find($fileId);
        if ($file) {

            $file->delete();

            return response()->json(['message' => 'File deleted successfully.'], 200);
        }

        return response()->json(['error' => 'File not found in this stage.'], 404);
    }
}
