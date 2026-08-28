<?php

namespace App\Http\Controllers;

use App\Models\Tool;
use App\Models\Parameter;
use Illuminate\Http\Request;
use App\Services\Executors\BaseToolsExecutor;
use App\Services\Executors\ContactExecutor;
use App\Services\ToolExecutor;
use App\Services\CodingExecutor;
use App\Services\Executors\SurveyExecutor;
use App\Services\Executors\RainbowExecutor;
use App\Services\Executors\RainbowDashboardTicketExecutor;
use App\Services\Executors\RainbowKnowledgeBaseExecutor;
use Illuminate\Support\Facades\Log;

class ToolController extends Controller
{
    // Fetch all tools with their parameters and groups
    public function index(Request $request)
    {


        $user = $request->user();

        

        if($user->hasRole('admin') || $user->hasRole('tools_admin')){
            $tools = Tool::with(['parameters', 'groups','assistants'])->get();
        }else{


            foreach($user->toolGroups as $tool_group){
                foreach($tool_group->tools()->with('parameters', 'groups', 'assistants')->get() as $tool){
                    $tools[] = $tool;
                }
            }

            
        }

        return response()->json($tools);
    }

    // Fetch a specific tool by ID
    public function show($id)
    {
        $tool = Tool::with(['parameters', 'groups'])->findOrFail($id);
        return response()->json($tool);
    }

    // Create a new tool with parameters and groups
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'strict' => 'required|boolean',
            'parameters' => 'required|array',
            'parameters.*.name' => 'required|string|max:255',
            'parameters.*.type' => 'required|string|max:255',
            'parameters.*.description' => 'nullable|string',
            'parameters.*.required' => 'required|boolean',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:tool_groups,id'
        ]);

        $tool = Tool::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'strict' => $validatedData['strict'],
        ]);

        foreach ($validatedData['parameters'] as $paramData) {
            $tool->parameters()->create([
                'name' => $paramData['name'],
                'type' => $paramData['type'],
                'description' => $paramData['description'] ?? null,
                'required' => $paramData['required'],
            ]);
        }

        if (isset($validatedData['group_ids'])) {
            $tool->groups()->sync($validatedData['group_ids']);
        }

        return response()->json(['message' => 'Tool created successfully', 'tool' => $tool->load(['parameters', 'groups'])], 201);
    }

    // Update an existing tool and its parameters and groups
    public function update(Request $request, $id)
    {
        $tool = Tool::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'strict' => 'sometimes|boolean',
            'parameters' => 'sometimes|array',
            'parameters.*.id' => 'nullable|exists:parameters,id',
            'parameters.*.name' => 'required|string|max:255',
            'parameters.*.type' => 'required|string|max:255',
            'parameters.*.description' => 'nullable|string',
            'parameters.*.required' => 'required|boolean',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:tool_groups,id'
        ]);

        $tool->update($validatedData);

        if (isset($validatedData['parameters'])) {
            $tool->parameters()->delete();

            foreach ($validatedData['parameters'] as $paramData) {
                if (isset($paramData['id'])) {
                    // Update existing parameter
                    $parameter = Parameter::findOrFail($paramData['id']);
                    $parameter->update($paramData);
                } else {
                    // Check for existing parameter
                    $existingParameter = $tool->parameters()->where('name', $paramData['name'])->first();
                    if (!$existingParameter) {
                        // Create a new parameter if it doesn't exist
                        $tool->parameters()->create($paramData);
                    }
                }
            }
        }

        if (isset($validatedData['group_ids'])) {
            $tool->groups()->sync($validatedData['group_ids']);
        }

        return response()->json(['message' => 'Tool updated successfully', 'tool' => $tool->load(['parameters', 'groups'])]);
    }

    // Delete a tool and its parameters
    public function destroy($id)
    {
        $tool = Tool::findOrFail($id);
        $tool->delete();

        return response()->json(['message' => 'Tool deleted successfully']);
    }

    // Remove a specific parameter from a tool
    public function deleteParameter($toolId, $paramId)
    {
        $parameter = Parameter::where('tool_id', $toolId)->findOrFail($paramId);
        $parameter->delete();

        return response()->json(['message' => 'Parameter deleted successfully']);
    }

    // Test a tool with provided parameters
    public function testTool(Request $request, $id)
    {
        $tool = Tool::with(['parameters'])->findOrFail($id);
        
        // Validate the request parameters
        $validationRules = [];
        $tool_parameters = $tool->parameters()->get();

        Log::info('[ToolController][TESTING TOOL] Testing tool', [
            'tool_id' => $tool->id,
            'tool_name' => $tool->name,
            'tool_parameters' => $tool_parameters ? $tool_parameters->toArray() : 'null',
            'parameters_count' => $tool_parameters ? $tool_parameters->count() : 0
        ]);
        Log::info('[ToolController][TESTING TOOL] Request parameters', [
            'request' => $request->all()
        ]);

   

        // Ensure parameters are loaded
        if (!$tool->parameters || $tool->parameters->isEmpty()) {
            // Try to reload the relationship
            $tool->load('parameters');
        }


        foreach ($tool_parameters as $parameter) {
            $rule = $parameter->required ? 'required' : 'nullable';
            $validationRules["parameters.{$parameter->name}"] = $rule;
        }
        
        $validatedData = $request->validate($validationRules);
        
        try {
            // Convert tool name to method name (handle spaces, hyphens, etc.)
            $methodName = strtolower(str_replace([' ', '-'], '_', $tool->name));
            
            // Initialize executors in order of preference
            $executors = [
                new BaseToolsExecutor($request->user()),
                new ContactExecutor($request->user()),
                new ToolExecutor($request->user()),
                new CodingExecutor($request->user()),
                new SurveyExecutor($request->user()),
                new RainbowExecutor($request->user()),
                new RainbowDashboardTicketExecutor($request->user()),
                new RainbowKnowledgeBaseExecutor($request->user()),
            ];
            
            $toolExecutor = null;
            $executorClass = null;
            
            // Find the first executor that has the method
            foreach ($executors as $executor) {
                if (method_exists($executor, $methodName)) {
                    $toolExecutor = $executor;
                    $executorClass = get_class($executor);
                    break;
                }
            }
            
            if (!$toolExecutor) {
                return response()->json([
                    'success' => false,
                    'error' => "Tool method '{$methodName}' not found in any executor"
                ], 400);
            }

            Log::info('[ToolController][FUNCTION CALLING] Executing method', [
                'tool_name' => $tool->name,
                'method_name' => $methodName,
                'executor_class' => $executorClass,
                'parameters' => $validatedData['parameters'] ?? []
            ]);

            // Execute the tool
            $result = $toolExecutor->$methodName($validatedData['parameters'] ?? []);
            
            Log::info('[ToolController][RESULT] Tool execution result', [
                'result' => $result,
                'result_type' => gettype($result),
                'result_class' => is_object($result) ? get_class($result) : 'not_object'
            ]);
            
            return response()->json([
                'success' => true,
                'result' => $result,
                'tool_name' => $tool->name,
                'method_name' => $methodName,
                'executor_class' => $executorClass,
                'parameters_used' => $validatedData['parameters'] ?? []
            ]);
            
        } catch (\Exception $e) {
            Log::error('[ToolController][ERROR] Tool execution failed', [
                'tool_id' => $tool->id,
                'tool_name' => $tool->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}


