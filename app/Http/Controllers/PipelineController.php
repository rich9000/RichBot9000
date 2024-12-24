<?php


namespace App\Http\Controllers;

use App\Models\Pipeline;
use Illuminate\Http\Request;
use App\Models\Stage;

class PipelineController extends Controller
{
    public function index()
    {

        $pipelines = Pipeline::with(['stages.assistants' => function($query) {
            $query->withPivot('order', 'success_stage_id', 'success_tool_id')->with('tools');
        },'stages.successTool','stages.files'])->get();


        return response()->json($pipelines);
    }

    public function show($id)
    {




        $pipeline = Pipeline::with([
            'stages' => function ($query) {
                $query->with([
                    'assistants' => function ($query) {
                        $query->withPivot('order', 'success_stage_id', 'success_tool_id')
                            ->with([
                                'successTool',  // Assistant-level success tool
                                'tools',
                                'model'

                            ]);
                    },
                    'successTool', // Stage-level success tool

                ]);
            }
        ])->findOrFail($id);









        return response()->json($pipeline);

        $pipeline = Pipeline::with(['stages.assistants' => function($query) {
            $query->withPivot('order', 'success_stage_id', 'success_tool_id');
        }, 'stages.successTool'])->findOrFail($id);

        return response()->json($pipeline);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $pipeline = Pipeline::create($validatedData);
        return response()->json($pipeline, 201);
    }

    public function update(Request $request, $id)
    {
        $pipeline = Pipeline::findOrFail($id);
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);
        $pipeline->update($validatedData);
        return response()->json($pipeline);
    }

    public function destroy($id)
    {
        $pipeline = Pipeline::findOrFail($id);
        $pipeline->delete();
        return response()->json(['message' => 'Pipeline deleted successfully']);
    }

    public function updateOrder(Request $request, $pipelineId)
    {
        $pipeline = Pipeline::findOrFail($pipelineId);
        $stages = $request->input('stages', []);

        foreach ($stages as $stageData) {
            Stage::where('pipeline_id', $pipelineId)
                ->where('id', $stageData['id'])
                ->update(['order' => $stageData['order']]);
        }

        return response()->json(['status' => 'success']);
    }
}
