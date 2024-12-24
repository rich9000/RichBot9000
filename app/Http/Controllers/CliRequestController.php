<?php

namespace App\Http\Controllers;

use App\Models\CliRequest;
use Illuminate\Http\Request;

class CliRequestController extends Controller
{
    public function index()
    {
        $requests = CliRequest::where('status', 'pending')->get();
        return response()->json($requests);
    }

    public function approve($id)
    {
        $request = CliRequest::findOrFail($id);
        $request->update(['status' => 'approved']);

        return response()->json(['success' => true, 'message' => 'CLI request approved.']);
    }

    public function reject($id)
    {
        $request = CliRequest::findOrFail($id);
        $request->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'CLI request rejected.']);
    }
}
