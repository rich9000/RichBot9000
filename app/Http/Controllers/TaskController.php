<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    // Display a listing of tasks
    public function index(Request $request)
    {
        $tasks = Task::with('project', 'users')->get();

        if ($request->wantsJson()) {
            return response()->json($tasks);
        }

        return view('tasks.index', compact('tasks'));
    }

    // Show the form for creating a new task
    public function create()
    {
        $projects = Project::all();
        $users    = User::all();

        return view('tasks.create', compact('projects', 'users'));
    }

    // Store a newly created task
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id'  => 'nullable|exists:projects,id',
            'order'       => 'nullable|integer',
            'user_ids'    => 'nullable|array',
            'user_ids.*'  => 'exists:users,id',
        ]);

        $task = new Task([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'user_id'     => auth()->id(),
            'project_id'  => $validated['project_id'] ?? null,
            'order'       => $validated['order'] ?? null,
        ]);

        $task->save();

        // Assign users to the task
        if (!empty($validated['user_ids'])) {
            $task->users()->sync($validated['user_ids']);
        }

        if ($request->wantsJson()) {
            return response()->json($task->load('users'), 201);
        }

        return redirect()->route('tasks.index')->with('success', 'Task created successfully.');
    }

    // Display the specified task
    public function show(Request $request, Task $task)
    {
        $task->load('project', 'users');

        if ($request->wantsJson()) {
            return response()->json($task);
        }

        return view('tasks.show', compact('task'));
    }

    // Show the form for editing the specified task
    public function edit(Task $task)
    {
        $projects = Project::all();
        $users    = User::all();
        $task->load('users');

        return view('tasks.edit', compact('task', 'projects', 'users'));
    }

    // Update the specified task
    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id'  => 'nullable|exists:projects,id',
            'order'       => 'nullable|integer',
            'user_ids'    => 'nullable|array',
            'user_ids.*'  => 'exists:users,id',
        ]);

        $task->update([
            'title'       => $validated['title'],
            'description' => $validated['description'] ?? null,
            'project_id'  => $validated['project_id'] ?? null,
            'order'       => $validated['order'] ?? null,
        ]);

        // Update assigned users
        if (isset($validated['user_ids'])) {
            $task->users()->sync($validated['user_ids']);
        } else {
            $task->users()->detach();
        }

        if ($request->wantsJson()) {
            return response()->json($task->load('users'));
        }

        return redirect()->route('tasks.index')->with('success', 'Task updated successfully.');
    }

    // Remove the specified task
    public function destroy(Request $request, Task $task)
    {
        $task->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('tasks.index')->with('success', 'Task deleted successfully.');
    }
}
