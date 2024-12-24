<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // Display a listing of projects
    public function index(Request $request)
    {
        $projects = Project::all();

        if ($request->wantsJson()) {
            return response()->json($projects);
        }

        return view('projects.index', compact('projects'));
    }

    // Show the form for creating a new project
    public function create()
    {
        return view('projects.create');
    }

    // Store a newly created project
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create($validated);

        if ($request->wantsJson()) {
            return response()->json($project, 201);
        }

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    // Display the specified project
    public function show(Request $request, Project $project)
    {
        if ($request->wantsJson()) {
            return response()->json($project);
        }

        return view('projects.show', compact('project'));
    }

    // Show the form for editing the specified project
    public function edit(Project $project)
    {
        return view('projects.edit', compact('project'));
    }

    // Update the specified project
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        if ($request->wantsJson()) {
            return response()->json($project);
        }

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    // Remove the specified project
    public function destroy(Request $request, Project $project)
    {
        $project->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
