<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Visitor;
use App\Models\Employee;
use Illuminate\Support\Facades\Storage;

class VisitorController extends Controller
{
    /**
     * Show visitor registration form
     */
    public function register()
    {
        $employees = Employee::all();
        $todaysVisitors = Visitor::whereDate('date', today())->latest()->get();
        
        return view('visitor.register', compact('employees', 'todaysVisitors'));
    }

    /**
     * Store visitor registration
     */
    public function store(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'person_to_visit' => 'required|exists:employees,id',
            'purpose' => 'required|string|max:255',
            'remarks' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,bmp|max:5120', // 5MB max
        ]);

        $data = $validated;
        $data['date'] = today();
        $data['time_in'] = now(); // Store as DATETIME

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('visitors', $filename, 'public');
            $data['photo_path'] = 'storage/' . $path;
        }

        $visitor = Visitor::create($data);

        return response()->json([
            'message' => 'Visitor registered successfully',
            'visitor' => $visitor
        ], 201);
    }

    /**
     * Check out visitor
     */
    public function checkOut($id)
    {
        $visitor = Visitor::findOrFail($id);
        $visitor->update([
            'time_out' => now() // Store as DATETIME
        ]);

        return response()->json([
            'message' => 'Visitor checked out successfully',
            'visitor' => $visitor
        ]);
    }

    /**
     * Get today's visitors (JSON API)
     */
    public function getTodaysVisitors()
    {
        $visitors = Visitor::whereDate('date', today())
            ->with('employee')
            ->latest()
            ->get()
            ->map(function($visitor) {
                return [
                    'id' => $visitor->id,
                    'full_name' => $visitor->full_name,
                    'phone' => $visitor->phone,
                    'person_to_visit_name' => $visitor->person_to_visit_name ?? 'N/A',
                    'purpose' => $visitor->purpose,
                    'remarks' => $visitor->remarks,
                    'time_in' => $visitor->time_in ? $visitor->time_in->format('H:i:s') : null,
                    'time_out' => $visitor->time_out ? $visitor->time_out->format('H:i:s') : null,
                    'date' => $visitor->date ? $visitor->date->format('Y-m-d') : null,
                    'photo_path' => $visitor->photo_url,
                    'status' => $visitor->status_string,
                ];
            });

        return response()->json($visitors);
    }
}
