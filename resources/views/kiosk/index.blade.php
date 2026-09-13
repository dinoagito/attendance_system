@extends('layouts.public')

@section('title', 'Public Kiosk')

@section('content')
<div class="page-header" style="text-align:center;">
    <h1>Welcome — Attendance Kiosk</h1>
    <p>Choose a public service. Admin functions require login.</p>
</div>

<div class="row justify-content-center">
    <div class="col-md-6 mb-4">
        <div class="card" style="text-align:center; padding:10px;">
            <div class="card-body">
                <div style="font-size:48px; color:var(--primary-color); margin-bottom:12px;"><i class="fas fa-address-book"></i></div>
                <h5>Register Visitor</h5>
                <p style="color:#6c757d; font-size:14px;">Kiosk visitor check-in with photo</p>
                <a href="{{ route('visitor.register') }}" class="btn btn-primary w-100"><i class="fas fa-user-plus"></i> Open Visitor Registration</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card" style="text-align:center; padding:10px;">
            <div class="card-body">
                <div style="font-size:48px; color:var(--primary-color); margin-bottom:12px;"><i class="fas fa-fingerprint"></i></div>
                <h5>Biometric Scan (ZK9500) - Employee/Faculty</h5>
                <p style="color:#6c757d; font-size:14px;">Fingerprint attendance for employees and faculty</p>
                <a href="{{ route('scan.employee.zk9500') }}" class="btn btn-primary w-100"><i class="fas fa-fingerprint"></i> Open Biometric Scan</a>
            </div>
        </div>
    </div>
</div>

<div style="text-align:center; margin-top:10px;">
    <a href="{{ route('login') }}" class="btn btn-outline-secondary"><i class="fas fa-user-shield"></i> Admin Login</a>
</div>
@endsection
