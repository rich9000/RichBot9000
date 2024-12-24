<?php
namespace App\Http\Controllers;

use App\Models\SqlRequest;
use Illuminate\Http\Request;

class SqlRequestController extends Controller
{
    public function index()
    {
        $requests = SqlRequest::where('status', 'pending')->get();
        return response()->json($requests);
    }

    public function approve($id)
    {
        $request = SqlRequest::findOrFail($id);
        $request->update(['status' => 'approved']);

        return response()->json(['success' => true, 'message' => 'SQL request approved.']);
    }

    public function reject($id)
    {
        $request = SqlRequest::findOrFail($id);
        $request->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'SQL request rejected.']);
    }
}
