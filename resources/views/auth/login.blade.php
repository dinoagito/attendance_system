<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login</title>
    <link href="{{ asset('vendor/bootstrap/5.3.0/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/6.4.0/css/all.min.css') }}" rel="stylesheet">
    <style>
    body {
        min-height: 100vh;
        margin: 0;
        display: grid;
        place-items: center;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        background: #f9f7f2;
        background-attachment: fixed;
    }

    .auth-shell {
        width: min(420px, calc(100vw - 32px));
        background: rgba(255,255,255,0.98);
        border-radius: 18px;
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.08);
    }

    .auth-header {
        /* MATCH HEADER GREEN STYLE */
        background: linear-gradient(90deg, #0b5d1e, #148a34);
        color: #fff;
        padding: 28px;
        text-align: center;
    }

    .auth-header h1 {
        font-size: 1.6rem;
        margin: 0 0 6px;
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .auth-body {
        padding: 28px;
    }

    .form-label {
        font-weight: 600;
    }

    .btn-ggc {
        background: linear-gradient(90deg, #0b5d1e, #1f9d3a);
        color: #fff;
        border: none;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
    }

    .btn-ggc:hover {
        background: linear-gradient(90deg, #0f6a22, #25b345);
    }

    .auth-shell p {
        opacity: 0.95;
    }
</style>
</head>
<body>
    <div class="auth-shell">
        <div class="auth-header">
            <h1><i class="fas fa-user-shield"></i> Admin Login</h1>
        </div>
        <div class="auth-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="login">Name</label>
                    <input id="login" type="text" name="login" class="form-control form-control-lg" value="{{ old('login') }}" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input id="password" type="password" name="password" class="form-control form-control-lg" required>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-check-label">
                        <input type="checkbox" name="remember" class="form-check-input me-1"> Remember me
                    </label>
                    <a href="{{ route('register') }}">Create account</a>
                </div>
                <button type="submit" class="btn btn-ggc btn-lg w-100">Login</button>
            </form>
        </div>
    </div>
</body>
</html>
