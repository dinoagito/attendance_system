<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Attendance Monitoring System</title>
    <link href="{{ asset('vendor/bootstrap/5.3.0/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/6.4.0/css/all.min.css') }}" rel="stylesheet">
    <style>
        :root {
            --primary-color: #008102;
            --secondary-color: #007BFF;
            --success-color: #00890A;
            --danger-color: #e74c3c;
            --warning-color: #FFB50F;
            --text-primary: #212529;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f7f2;
            color: var(--text-primary);
            min-height: 100vh;
        }

        .public-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .public-topbar {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .public-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-color);
            font-weight: 700;
            text-decoration: none;
        }

        .public-brand:hover {
            color: var(--primary-color);
        }

        .public-badge {
            font-size: 12px;
            color: #6c757d;
            background: #f1f3f5;
            padding: 5px 10px;
            border-radius: 999px;
        }

        .public-content {
            flex: 1;
            overflow-y: auto;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .page-header p {
            color: #999;
            font-size: 14px;
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            padding: 20px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .card-body {
            padding: 20px;
        }

        .btn {
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #006600;
            color: white;
        }

        .form-control,
        .form-select {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 129, 2, 0.25);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        .profile-pic,
        .profile-pic-large {
            object-fit: cover;
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0e0e0;
        }

        .profile-pic-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }

        @media (max-width: 768px) {
            .public-content {
                padding: 20px 15px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .public-badge {
                display: none;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="public-shell">
        <header class="public-topbar">
            <a href="{{ url('/') }}" class="public-brand">
                <i class="fas fa-clipboard-list"></i>
                <span>Attendance Monitoring System</span>
            </a>
        </header>

        <main class="public-content">
            @yield('content')
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/5.3.0/js/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts')
</body>
</html>
