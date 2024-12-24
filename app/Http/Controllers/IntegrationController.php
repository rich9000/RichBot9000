<?php

namespace App\Http\Controllers;

use App\Models\Integration;
use App\Http\Requests\IntegrationRequest;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function index()
    {
        $integrations = Integration::when(auth()->user()->is_admin, function ($query) {
            return $query;
        }, function ($query) {
            return $query->where('user_id', auth()->id());
        })->get();

        return response()->json($integrations);
    }

    public function create()
    {
        return view('integrations.create');
    }

    public function store(IntegrationRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        
        Integration::create($data);

        return redirect()->route('integrations.index')
            ->with('success', 'Integration created successfully.');
    }

    public function edit(Integration $integration)
    {
        return view('integrations.edit', compact('integration'));
    }

    public function update(IntegrationRequest $request, Integration $integration)
    {
        $integration->update($request->validated());

        return redirect()->route('integrations.index')
            ->with('success', 'Integration updated successfully.');
    }

    public function destroy(Integration $integration)
    {
        $integration->delete();

        return redirect()->route('integrations.index')
            ->with('success', 'Integration deleted successfully.');
    }
} 