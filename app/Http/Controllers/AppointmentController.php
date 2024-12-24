<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the appointments.
     */
    public function index()
    {
        $appointments = Appointment::all();

        return response()->json($appointments);
    }

    /**
     * Store a newly created appointment in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time'  => 'required|date',
            'end_time'    => 'required|date|after_or_equal:start_time',
            'all_day'     => 'sometimes|boolean',
        ]);

        // Create a new appointment
        $appointment = Appointment::create($request->all());

        return response()->json($appointment, 201);
    }

    /**
     * Display the specified appointment.
     */
    public function show(Appointment $appointment)
    {
        return response()->json($appointment);
    }

    /**
     * Update the specified appointment in storage.
     */
    public function update(Request $request, Appointment $appointment)
    {
        // Validate the request data
        $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_time'  => 'sometimes|required|date',
            'end_time'    => 'sometimes|required|date|after_or_equal:start_time',
            'all_day'     => 'sometimes|boolean',
        ]);

        // Update the appointment
        $appointment->update($request->all());

        return response()->json($appointment);
    }

    /**
     * Remove the specified appointment from storage.
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json(null, 204);
    }
}
