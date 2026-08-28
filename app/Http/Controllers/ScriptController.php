<?php

namespace App\Http\Controllers;

use App\Models\Script;
use App\Services\ScriptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScriptController extends Controller
{
    protected $scriptService;

    public function __construct(ScriptService $scriptService)
    {
        $this->scriptService = $scriptService;
    }

    public function index()
    {
        $scripts = Script::with('user')->get();
        return response()->json($scripts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'path' => 'required|string|max:255',
            'parameters' => 'nullable|array',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['user_id'] = auth()->id();

        $script = $this->scriptService->create($data);
        return response()->json($script, 201);
    }

    public function show(Script $script)
    {


   
        return response()->json($script->load('user'));
    }

    public function update(Request $request, Script $script)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'type' => 'string|max:255',
            'path' => 'string|max:255',
            'parameters' => 'nullable|array',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $script = $this->scriptService->update($script, $request->all());
        return response()->json($script);
    }

    public function destroy(Script $script)
    {
        $this->scriptService->delete($script);
        return response()->json(null, 204);
    }

    public function execute(Script $script)
    {
        $result = $this->scriptService->execute($script);
        return response()->json(['success' => $result]);
    }

    public function getContent(Script $script)
    {
        $content = $this->scriptService->getScriptContent($script);
        return response()->json(['content' => $content]);
    }
} 