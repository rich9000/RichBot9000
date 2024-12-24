<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class ContentController extends Controller
{
    /**
     * Show the main application page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Load dynamic content based on the section parameter.
     *
     * @param string $section
     * @return \Illuminate\Http\Response
     */
    public function getContent(Request $request, $section)
    {
        // Check if the view exists using the dot notation
        if (!View::exists($section)) {
            return response()->json(['message' => 'Section not found.'], 404);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Check if the request expects a JSON response
        if ($request->wantsJson()) {
            return response()->json([
                'user' => $user,
                'content' => view($section, ['user' => $user])->render(),
            ]);
        }

        // Return the view content with the user included
        return view($section, ['user' => $user]);
    }
}
