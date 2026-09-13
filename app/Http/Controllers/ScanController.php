<?php

namespace App\Http\Controllers;

class ScanController extends Controller
{
    /**
     * Show biometric employee scan page (legacy - primary is WebAuthnController)
     */
    public function employeeScan()
    {
        return view('scan.employee');
    }
}
