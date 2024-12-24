<?php

// app/Http/Controllers/FileChangeRequestController.php
namespace App\Http\Controllers;

use App\Models\FileChangeRequest;
use Illuminate\Http\Request;

class FileChangeRequestController extends Controller
{
    // View pending file change requests
    public function index()
    {
        $requests = FileChangeRequest::where('status', 'pending')->get();
        return response()->json($requests);
    }

    // Approve a file change request
    public function approve($id)
    {
        $request = FileChangeRequest::findOrFail($id);

        try {
            // Save new content to the disk
            $this->disk->put($request->file_path, base64_decode($request->new_content));

            // Update request status to 'approved'
            $request->update(['status' => 'approved']);

            return response()->json(['success' => true, 'message' => 'File change approved and updated.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Failed to approve file change: ' . $e->getMessage()]);
        }
    }

    // Reject a file change request
    public function reject($id)
    {
        $request = FileChangeRequest::findOrFail($id);
        $request->update(['status' => 'rejected']);
        return response()->json(['success' => true, 'message' => 'File change rejected.']);
    }
}
