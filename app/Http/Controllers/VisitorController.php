<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
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
        try {
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'person_to_visit' => 'required|exists:employees,id',
                'purpose' => 'required|string|max:255',
                'remarks' => 'nullable|string',
                'photo' => 'nullable|image|mimes:jpeg,png,bmp|max:5120', // 5MB max
                'photo_base64' => 'nullable|string',
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
            } elseif ($request->filled('photo_base64') && str_starts_with($request->input('photo_base64'), 'data:image/')) {
                $photoData = $request->input('photo_base64');
                [$meta, $encoded] = explode(',', $photoData, 2);
                $mime = str_contains($meta, 'image/png') ? 'png' : (str_contains($meta, 'image/bmp') ? 'bmp' : 'jpg');
                $decoded = base64_decode($encoded);

                if ($decoded !== false) {
                    $filename = time() . '_' . uniqid() . '.' . $mime;
                    $relativePath = 'visitors/' . $filename;
                    Storage::disk('public')->put($relativePath, $decoded);
                    $data['photo_path'] = 'storage/' . $relativePath;
                }
            }

            $visitor = Visitor::create($data);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Visitor registered successfully',
                    'visitor' => $visitor
                ], 201);
            }

            return back()->with('success', 'Visitor registered successfully.');
        } catch (
            \Illuminate\Validation\ValidationException $e
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please check the form fields and try again.',
                    'errors' => $e->errors(),
                ], 422);
            }

            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error registering visitor. Please try again.',
                ], 500);
            }

            return back()->withErrors(['error' => 'Error registering visitor. Please try again.'])->withInput();
        }
    }

    /**
     * Check out visitor - records actual current date/time, prevents duplicate checkout
     */
    public function checkOut($id)
    {
        $visitor = Visitor::findOrFail($id);

        // Prevent duplicate checkout - if already checked out, return existing record without overwriting
        if ($visitor->time_out) {
            $visitor->refresh();
            $timeIn = $visitor->time_in ? Carbon::parse($visitor->time_in)->timezone('Asia/Manila') : null;
            $timeOut = $visitor->time_out ? Carbon::parse($visitor->time_out)->timezone('Asia/Manila') : null;

            return response()->json([
                'message' => 'Visitor already checked out.',
                'checked_out_at' => $timeOut ? $timeOut->format('Y-m-d H:i:s') : null,
                'checked_out_at_display' => $timeOut ? $timeOut->format('M d, Y h:i A') : null,
                'visitor' => [
                    'id' => $visitor->id,
                    'full_name' => $visitor->full_name,
                    'phone' => $visitor->phone,
                    'person_to_visit_name' => $visitor->person_to_visit_name ?? 'N/A',
                    'purpose' => $visitor->purpose,
                    'photo_path' => $visitor->photo_url,
                    'time_in' => $timeIn ? $timeIn->format('H:i:s') : null,
                    'time_out' => $timeOut ? $timeOut->format('H:i:s') : null,
                    'duration' => $visitor->duration,
                    'status' => $visitor->status_string,
                ]
            ], 409);
        }

        // Record actual current date/time automatically - no manual input
        $checkedOutAt = Carbon::now('Asia/Manila');

        $visitor->update([
            'time_out' => $checkedOutAt, // Store as DATETIME
        ]);

        $visitor->refresh();

        $timeIn = $visitor->time_in ? Carbon::parse($visitor->time_in)->timezone('Asia/Manila') : null;
        $timeOut = $visitor->time_out ? Carbon::parse($visitor->time_out)->timezone('Asia/Manila') : null;

        return response()->json([
            'message' => 'Visitor checked out successfully.',
            'checked_out_at' => $checkedOutAt->format('Y-m-d H:i:s'),
            'checked_out_at_display' => $checkedOutAt->format('M d, Y h:i A'),
            'visitor' => [
                'id' => $visitor->id,
                'full_name' => $visitor->full_name,
                'phone' => $visitor->phone,
                'person_to_visit_name' => $visitor->person_to_visit_name ?? 'N/A',
                'purpose' => $visitor->purpose,
                'photo_path' => $visitor->photo_url,
                'time_in' => $timeIn ? $timeIn->format('H:i:s') : null,
                'time_out' => $timeOut ? $timeOut->format('H:i:s') : null,
                'duration' => $visitor->duration,
                'status' => $visitor->status_string,
            ]
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
                $timeIn = $visitor->time_in ? Carbon::parse($visitor->time_in)->timezone('Asia/Manila') : null;
                $timeOut = $visitor->time_out ? Carbon::parse($visitor->time_out)->timezone('Asia/Manila') : null;

                return [
                    'id' => $visitor->id,
                    'full_name' => $visitor->full_name,
                    'phone' => $visitor->phone,
                    'person_to_visit_name' => $visitor->person_to_visit_name ?? 'N/A',
                    'purpose' => $visitor->purpose,
                    'remarks' => $visitor->remarks,
                    'time_in' => $timeIn ? $timeIn->format('H:i:s') : null,
                    'time_out' => $timeOut ? $timeOut->format('H:i:s') : null,
                    'time_in_display' => $timeIn ? $timeIn->format('h:i A') : null,
                    'time_out_display' => $timeOut ? $timeOut->format('h:i A') : null,
                    'date' => $visitor->date ? Carbon::parse($visitor->date)->timezone('Asia/Manila')->format('Y-m-d') : null,
                    'date_display' => $visitor->date ? Carbon::parse($visitor->date)->timezone('Asia/Manila')->format('M d, Y') : null,
                    'photo_path' => $visitor->photo_url,
                    'status' => $visitor->status_string,
                ];
            });

        return response()->json($visitors);
    }
}
