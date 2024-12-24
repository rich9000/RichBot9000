<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        if ($user->id === 1) {
            // Admin sees all contacts in user_contacts table
            $contacts = Contact::whereHas('users')
                ->with('users')
                ->get();
        } else {
            // Regular users only see their non-deleted contacts
            $contacts = $user->contacts()
                ->wherePivot('context', '!=', 'deleted_contact')
                ->with('creator')
                ->get();
        }
        
        return response()->json([
            'draw' => request()->get('draw'),
            'recordsTotal' => $contacts->count(),
            'recordsFiltered' => $contacts->count(),
            'data' => $contacts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20', 
            'type' => 'nullable|string|max:50',
            'context' => 'nullable|string|max:50',
            'custom_name' => 'nullable|string|max:255',
            'allowed_to_contact' => 'nullable|boolean'
        ]);

        // Try to find existing contact by email only
        $contact = Contact::where('email', $validated['email'])->first();

        // If no existing contact, create new one
        if (!$contact) {
            $contact = Contact::create([
                'user_id' => auth()->id(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'type' => $validated['type'] ?? 'contact'
            ]);
        }

        // Check if user already has this contact
        if (!$request->user()->contacts()->where('contact_id', $contact->id)->exists()) {
            $request->user()->contacts()->attach($contact->id, [
                'context' => $validated['context'] ?? 'contact',
                'name' => $validated['custom_name'] ?? null,
                'allowed_to_contact' => $validated['allowed_to_contact'] ?? true
            ]);
        }

        // Load the relationship before returning
        $contact->load(['users' => function($query) {
            $query->where('users.id', auth()->id());
        }]);

        return response()->json($contact, 201);
    }

    public function show(Contact $contact)
    {
        $contact->load(['users' => function($query) {
            $query->where('users.id', auth()->id());
        }]);
        
        return response()->json($contact);
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:50',
            'context' => 'nullable|string|max:50',
            'custom_name' => 'nullable|string|max:255',
            'allowed_to_contact' => 'nullable|boolean'
        ]);

        // Update contact details
        $contact->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'type' => $validated['type'] ?? 'contact'
        ]);

        // Update the pivot data with allowed_to_contact
        if (!$request->user()->contacts()->where('contact_id', $contact->id)->exists()) {
            $request->user()->contacts()->attach($contact->id, [
                'context' => $validated['context'] ?? 'contact',
                'name' => $validated['custom_name'] ?? null,
                'allowed_to_contact' => $validated['allowed_to_contact'] ?? true
            ]);
        } else {
            $request->user()->contacts()->syncWithoutDetaching([
                $contact->id => [
                    'context' => $validated['context'] ?? 'contact',
                    'name' => $validated['custom_name'] ?? null,
                    'allowed_to_contact' => $validated['allowed_to_contact'] ?? true
                ]
            ]);
        }

        // Reload the contact with pivot data to verify changes
        $contact->load(['users' => function($query) use ($request) {
            $query->where('users.id', $request->user()->id)
                  ->select('users.id')
                  ->withPivot('context', 'allowed_to_contact', 'name');
        }]);

        return response()->json($contact);
    }

    public function destroy(Contact $contact)
    {
      //  $this->authorize('update', $contact);
        
        // Instead of deleting the contact, update the pivot relationship
        auth()->user()->contacts()->updateExistingPivot($contact->id, [
            'context' => 'deleted_contact',
            'allowed_to_contact' => false
        ]);

        return response()->json(null, 200);
    }
}
