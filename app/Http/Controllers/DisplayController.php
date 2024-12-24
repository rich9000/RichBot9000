<?php

namespace App\Http\Controllers;

use App\Models\Display;
use Illuminate\Http\Request;

class DisplayController extends Controller
{
    // List all displays
    public function index()
    {
        $displays = Display::all();
        return response()->json($displays);
    }

    // Show a specific display
    public function show($id)
    {
        $display = Display::where(['id'=> $id,'status'=>1])->first();

        if(!$display){
            $display = Display::where(['name'=> $id,'status'=>1])->first();
        }

        if(request()->wantsJson()||request()->ajax() || request()->header('Content-Type') == 'application/json'){

            return response()->json($display);
        }

        return view('richbot.display',[ 'display' => $display ]);


    }

    // Create a new display
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'content' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        if ($request->status) {
            // Deactivate any active display with the same name
            Display::where('name', $request->name)->where('status', true)->update(['status' => false]);
        }

        $display = Display::create($request->all());

        return response()->json(['message' => 'Display created successfully', 'display' => $display]);
    }

    // Update a display
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'content' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        $display = Display::findOrFail($id);

        if ($request->status) {
            // Deactivate any active display with the same name
            Display::where('name', $request->name)->where('status', true)->update(['status' => false]);
        }

        $display->update($request->all());

        return response()->json(['message' => 'Display updated successfully', 'display' => $display]);
    }

    // Delete a display
    public function destroy($id)
    {
        $display = Display::findOrFail($id);
        $display->delete();

        return response()->json(['message' => 'Display deleted successfully']);
    }
}
