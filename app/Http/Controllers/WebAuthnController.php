<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeCredential;
use App\Models\EmployeeAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class WebAuthnController extends Controller
{
    /**
     * Show the biometric scan page (Windows Hello)
     */
    public function scanPage()
    {
        $employees = Employee::orderBy('first_name')->orderBy('last_name')->get();
        return view('scan.employee', compact('employees'));
    }

    /**
     * Show the ZK9500 fingerprint scanner page
     */
    public function zk9500Page()
    {
        $employees = Employee::orderBy('first_name')->orderBy('last_name')->get();
        $todayAttendance = EmployeeAttendance::with('employee')
            ->whereDate('date', now()->toDateString())
            ->orderBy('time_in', 'desc')
            ->get();

        return view('scan.zk9500', compact('employees', 'todayAttendance'));
    }

    /**
     * Show the separate ZK9500 prototype page
     */
    public function zk9500PrototypePage()
    {
        $employees = Employee::orderBy('first_name')->orderBy('last_name')->get();
        $todayAttendance = EmployeeAttendance::with('employee')
            ->whereDate('date', now()->toDateString())
            ->orderBy('time_in', 'desc')
            ->get();

        return view('scan.zk9500_prototype', compact('employees', 'todayAttendance'));
    }

    /**
     * Show the ZK9500 enrollment page (dedicated)
     */
    public function zk9500EnrollPage(Request $request)
    {
        $employees = Employee::orderBy('first_name')->orderBy('last_name')->get();

        $employeeDirectory = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'employeeNo' => $emp->employee_id_number,
                'name' => $emp->name,
            ];
        })->toArray();

        return view('scan.zk9500_enroll', compact('employees', 'employeeDirectory'));
    }

    /**
     * Proxy fingerprint-server /health to avoid CORS/port issues
     */
    public function fingerprintHealthProxy(Request $request)
    {
        try {
            $res = Http::timeout(2)->get('http://127.0.0.1:3001/health');
            return response($res->body(), $res->status())->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'proxy error', 'error' => $e->getMessage()], 502);
        }
    }

    /**
     * Get registration options (challenge) for WebAuthn
     */
    public function getRegistrationOptions(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        // Generate a random challenge
        $challenge = random_bytes(32);
        
        // Store challenge in session for verification
        Session::put('webauthn_challenge', base64_encode($challenge));
        Session::put('webauthn_employee_id', $employee->id);

        // WebAuthn registration options
        $options = [
            'challenge' => $this->base64UrlEncode($challenge),
            'rp' => [
                'name' => config('app.name', 'Attendance System'),
                'id' => $request->getHost(),
            ],
            'user' => [
                'id' => $this->base64UrlEncode($employee->id . '-' . $employee->employee_id_number),
                'name' => $employee->employee_id_number,
                'displayName' => $employee->name,
            ],
            'pubKeyCredParams' => [
                ['alg' => -7, 'type' => 'public-key'],   // ES256
                ['alg' => -257, 'type' => 'public-key'], // RS256
            ],
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform', // Use built-in authenticator (Windows Hello)
                'userVerification' => 'required',
                'residentKey' => 'preferred',
            ],
            'timeout' => 60000,
            'attestation' => 'none',
        ];

        return response()->json([
            'success' => true,
            'options' => $options,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_id' => $employee->employee_id_number,
            ]
        ]);
    }

    /**
     * Complete registration - store the credential
     */
    public function completeRegistration(Request $request)
    {
        $request->validate([
            'credential_id' => 'required|string',
            'public_key' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $employeeId = Session::get('webauthn_employee_id');
        
        if (!$employeeId) {
            return response()->json([
                'success' => false,
                'message' => 'Registration session expired. Please try again.'
            ], 400);
        }

        $employee = Employee::findOrFail($employeeId);

        // Check if credential already exists
        $existing = EmployeeCredential::where('credential_id', $request->credential_id)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This biometric is already registered.'
            ], 400);
        }

        // Delete any existing credentials for this employee (one credential per employee)
        EmployeeCredential::where('employee_id', $employeeId)->delete();

        // Store the new credential
        EmployeeCredential::create([
            'employee_id' => $employeeId,
            'credential_id' => $request->credential_id,
            'public_key' => $request->public_key,
            'device_name' => $request->device_name ?? 'Windows Hello',
            'sign_count' => 0,
        ]);

        // Clear session
        Session::forget(['webauthn_challenge', 'webauthn_employee_id']);

        return response()->json([
            'success' => true,
            'message' => "Biometric enrolled successfully for {$employee->name}!",
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_id' => $employee->employee_id_number,
            ]
        ]);
    }

    /**
     * Get authentication options for verification
     */
    public function getAuthenticationOptions(Request $request)
    {
        // Get all registered credentials
        $credentials = EmployeeCredential::with('employee')->get();

        if ($credentials->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No biometrics enrolled. Please enroll first.'
            ], 400);
        }

        // Generate challenge
        $challenge = random_bytes(32);
        Session::put('webauthn_auth_challenge', base64_encode($challenge));

        // Build allowed credentials list
        $allowCredentials = $credentials->map(function ($cred) {
            return [
                'id' => $cred->credential_id,
                'type' => 'public-key',
                'transports' => ['internal'],
            ];
        })->toArray();

        $options = [
            'challenge' => $this->base64UrlEncode($challenge),
            'rpId' => $request->getHost(),
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'required',
            'timeout' => 60000,
        ];

        return response()->json([
            'success' => true,
            'options' => $options,
        ]);
    }

    /**
     * Verify authentication and record attendance
     */
    public function verifyAuthentication(Request $request)
    {
        $request->validate([
            'credential_id' => 'required|string',
        ]);

        // Find the credential
        $credential = EmployeeCredential::with('employee')
            ->where('credential_id', $request->credential_id)
            ->first();

        if (!$credential) {
            return response()->json([
                'success' => false,
                'matched' => false,
                'message' => 'Biometric not recognized. Please enroll first.'
            ]);
        }

        // Update sign count
        $credential->increment('sign_count');

        // Record attendance
        $employee = $credential->employee;
        $today = now()->toDateString();

        $attendance = EmployeeAttendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        $action = 'time_in';
        $message = '';

        if (!$attendance) {
            // First scan - Time In
            $timeIn = now();
            $status = $timeIn->format('H:i:s') <= '08:00:00' ? 'present' : 'late';
            
            $attendance = EmployeeAttendance::create([
                'employee_id' => $employee->id,
                'date' => $today,
                'time_in' => $timeIn,
                'status' => $status,
            ]);
            
            $action = 'time_in';
            $message = "Time In recorded at " . $timeIn->format('h:i A');
        } elseif (!$attendance->time_out) {
            // Second scan - Time Out
            $attendance->update([
                'time_out' => now(),
            ]);
            
            $action = 'time_out';
            $message = "Time Out recorded at " . now()->format('h:i A');
        } else {
            // Already completed for today
            $action = 'already_recorded';
            $message = "Attendance already complete for today";
        }

        // Clear session
        Session::forget('webauthn_auth_challenge');

        return response()->json([
            'success' => true,
            'matched' => true,
            'employee' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id_number,
                'name' => $employee->name,
                'department' => $employee->department,
                'position' => $employee->position ?? null,
            ],
            'attendance' => [
                'action' => $action,
                'message' => $message,
                'time_in' => $attendance->time_in?->format('h:i A'),
                'time_out' => $attendance->time_out?->format('h:i A'),
                'status' => $attendance->status,
            ],
            'message' => "Welcome, {$employee->name}! {$message}"
        ]);
    }

    /**
     * Get today's attendance records
     */
    public function getTodayAttendance()
    {
        $attendance = EmployeeAttendance::with('employee')
            ->whereDate('date', now()->toDateString())
            ->orderBy('time_in', 'desc')
            ->get()
            ->map(function ($record) {
                return [
                    'employee_name' => $record->employee->name ?? 'Unknown',
                    'employee_code' => $record->employee->employee_id_number ?? '-',
                    'department' => $record->employee->department ?? '-',
                    'time_in' => $record->time_in?->format('h:i A'),
                    'time_out' => $record->time_out?->format('h:i A'),
                    'status' => $record->status,
                ];
            });

        return response()->json([
            'success' => true,
            'attendance' => $attendance,
        ]);
    }

    /**
     * Get enrolled employees for enrollment page
     */
    public function getEnrolledStatus()
    {
        $employees = Employee::with('credential')->get()->map(function ($emp) {
            return [
                'id' => $emp->id,
                'employee_id' => $emp->employee_id_number,
                'name' => $emp->name,
                'department' => $emp->department,
                'enrolled' => $emp->credential !== null,
                'enrolled_at' => $emp->credential?->created_at?->format('M d, Y h:i A'),
            ];
        });

        return response()->json([
            'success' => true,
            'employees' => $employees,
        ]);
    }

    /**
     * Delete an employee's credential
     */
    public function deleteCredential(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        EmployeeCredential::where('employee_id', $request->employee_id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Biometric credential deleted successfully.'
        ]);
    }

    /**
     * Base64 URL encode (for WebAuthn)
     */
    private function base64UrlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
