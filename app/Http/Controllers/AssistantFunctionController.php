<?php

namespace App\Http\Controllers;

use App\Models\AssistantFunction;
use Illuminate\Http\Request;

class AssistantFunctionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            // Handle DataTables AJAX request
            $columns = ['id', 'name', 'description', 'status', 'version'];

            // DataTables pagination parameters
            $length = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');

            // Search filter
            $searchValue = $request->input('search.value');

            $query = AssistantFunction::select($columns);

            if (!empty($searchValue)) {
                $query->where(function($q) use ($searchValue) {
                    $q->where('name', 'LIKE', "%{$searchValue}%")
                        ->orWhere('description', 'LIKE', "%{$searchValue}%")
                        ->orWhere('status', 'LIKE', "%{$searchValue}%")
                        ->orWhere('version', 'LIKE', "%{$searchValue}%");
                });
            }

            $totalData = AssistantFunction::count();
            $totalFiltered = $query->count();

            $functions = $query->offset($start)
                ->limit($length)
                ->orderBy($order, $dir)
                ->get();

            $data = $functions->map(function ($function) {
                return [
                    'id' => $function->id,
                    'name' => $function->name,
                    'description' => $function->description,
                    'status' => ucfirst($function->status),
                    'version' => $function->version,
                    'actions' => '' // Actions are handled in JavaScript
                ];
            });

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $totalData,
                'recordsFiltered' => $totalFiltered,
                'data' => $data,
            ]);
        }

        return view('assistant_functions.index');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Create view not available for API.']);
        }

        return view('assistant_functions.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parameters' => 'nullable|json',
            'code' => 'nullable|string',
            'status' => 'required|in:active,inactive,deprecated',
            'version' => 'required|string|max:10',
        ]);

        $function = AssistantFunction::create($validated);

        if ($request->wantsJson()) {
            return response()->json($function, 201);
        }

        return redirect()->route('assistant_functions.index')->with('success', 'Assistant Function created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssistantFunction  $assistantFunction
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, AssistantFunction $assistantFunction)
    {
        if ($request->wantsJson()) {
            return response()->json($assistantFunction);
        }

        return view('assistant_functions.show', compact('assistantFunction'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssistantFunction  $assistantFunction
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, AssistantFunction $assistantFunction)
    {
        if ($request->wantsJson()) {
            return response()->json(['message' => 'Edit view not available for API.']);
        }

        return view('assistant_functions.edit', compact('assistantFunction'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssistantFunction  $assistantFunction
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, AssistantFunction $assistantFunction)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parameters' => 'nullable|json',
            'code' => 'nullable|string',
            'status' => 'required|in:active,inactive,deprecated',
            'version' => 'required|string|max:10',
        ]);

        $assistantFunction->update($validated);

        if ($request->wantsJson()) {
            return response()->json($assistantFunction);
        }

        return redirect()->route('assistant_functions.index')->with('success', 'Assistant Function updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssistantFunction  $assistantFunction
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, AssistantFunction $assistantFunction)
    {
        $assistantFunction->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Assistant Function deleted successfully.']);
        }

        return redirect()->route('assistant_functions.index')->with('success', 'Assistant Function deleted successfully.');
    }
}
