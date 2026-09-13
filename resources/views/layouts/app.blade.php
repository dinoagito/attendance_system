<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - Attendance Monitoring System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #008102;
            --secondary-color: #007BFF;
            --success-color: #00890A;
            --danger-color: #e74c3c;
            --warning-color: #FFB50F;
            --light-bg: #FFF8DC;
            --text-primary: #212529;
            --text-secondary: #C1C1C1;
            --sidebar-width: 250px;
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
        }

        .wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color), #34495e);
            color: white;
            padding: 20px;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 20px;
            font-weight: bold;
        }

        .sidebar-brand i {
            margin-right: 10px;
            font-size: 24px;
        }

        .sidebar-nav {
            list-style: none;
        }

        .sidebar-nav-item {
            margin-bottom: 10px;
        }

        .sidebar-nav-link {
            display: block;
            padding: 12px 15px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar-nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            padding-left: 20px;
        }

        .sidebar-nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .sidebar-nav-link i {
            margin-right: 10px;
            width: 20px;
        }

        /* Main Content */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Bar */
        .topbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            border-bottom: 1px solid #e0e0e0;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar-toggle {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--primary-color);
            cursor: pointer;
        }

        .topbar-search {
            position: relative;
        }

        .topbar-search input {
            padding: 8px 15px 8px 35px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            background-color: #fafbfc;
        }

        .topbar-search input:focus {
            border-color: var(--primary-color);
            background-color: white;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 129, 2, 0.1);
        }

        .topbar-search i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .topbar-icon-btn {
            background: none;
            border: none;
            font-size: 18px;
            color: var(--primary-color);
            cursor: pointer;
            position: relative;
        }

        .topbar-icon-btn .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            font-size: 11px;
        }

        .topbar-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .topbar-profile-pic {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        /* Content Area */
        .content {
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

        /* Cards */
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

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }

        .stat-card-icon {
            font-size: 32px;
            margin-bottom: 15px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-left: auto;
            margin-right: auto;
        }

        .stat-card-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 10px 0;
        }

        .stat-card-label {
            font-size: 13px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card.blue .stat-card-icon {
            background-color: rgba(0, 123, 255, 0.1);
            color: var(--secondary-color);
        }

        .stat-card.green .stat-card-icon {
            background-color: rgba(0, 129, 2, 0.1);
            color: var(--primary-color);
        }

        .stat-card.red .stat-card-icon {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .stat-card.orange .stat-card-icon {
            background-color: rgba(255, 181, 15, 0.1);
            color: var(--warning-color);
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background-color: #f8f9fa;
        }

        .table thead th {
            border-bottom: 2px solid #e0e0e0;
            color: var(--primary-color);
            font-weight: 600;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e0e0e0;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-on-time {
            background-color: rgba(0, 129, 2, 0.1);
            color: var(--success-color);
        }

        .badge-late {
            background-color: rgba(255, 181, 15, 0.1);
            color: var(--warning-color);
        }

        .badge-absent {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .badge-excused {
            background-color: rgba(0, 123, 255, 0.1);
            color: var(--secondary-color);
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
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

        /* Schedule button (use primary color, not blue) */
        .btn-schedule {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 6px 10px;
            font-size: 0.85rem;
            border-radius: 4px;
        }

        .btn-schedule:hover {
            background-color: #006600;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        /* Forms */
        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 129, 2, 0.25);
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--primary-color);
        }

        /* Profile Images */
        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #e0e0e0;
        }

        .profile-pic-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Action Buttons Group */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            background: none;
            border: none;
            font-size: 16px;
            cursor: pointer;
            padding: 5px 10px;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            color: var(--secondary-color);
            transform: scale(1.1);
        }

        .action-btn.delete:hover {
            color: var(--danger-color);
        }

        /* Modal */
        .modal-content {
            border: none;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-title {
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .wrapper {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                max-height: 60px;
                padding: 0 20px;
                overflow: visible;
            }

            .sidebar-brand {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
            }

            .sidebar-nav {
                display: none;
            }

            .sidebar.active .sidebar-nav {
                display: block;
                position: absolute;
                top: 60px;
                left: 0;
                background: linear-gradient(135deg, var(--primary-color), #34495e);
                width: 100%;
                z-index: 100;
            }

            .topbar {
                padding: 15px 20px;
            }

            .content {
                padding: 20px 15px;
            }

            .page-header h1 {
                font-size: 22px;
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-clipboard-list"></i>
                <span>Attendance Monitoring System</span>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-nav-item">
                    <a href="{{ route('dashboard') }}" class="sidebar-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="{{ route('attendance.employee') }}" class="sidebar-nav-link {{ request()->routeIs('attendance.employee') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span>Employee Attendance</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="{{ route('attendance.visitor') }}" class="sidebar-nav-link {{ request()->routeIs('attendance.visitor') ? 'active' : '' }}">
                        <i class="fas fa-user-check"></i>
                        <span>Visitor Log</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a href="{{ route('users.index') }}" class="sidebar-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="fas fa-user-circle"></i>
                        <span>Registration</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="main-wrapper">
            <!-- Top Bar -->
            <div class="topbar">
                <div class="topbar-left">
                    <button class="topbar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div class="topbar-right">
                
                    @auth
                    <div class="topbar-profile">
                        <div class="topbar-profile-pic">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div style="font-size: 14px; font-weight: 500;">{{ Auth::user()->name }}</div>
                            <div style="font-size: 12px; color: #999;">Administrator</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                    @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">Login</a>
                    @endauth
                </div>
            </div>

            <!-- Content Area -->
            <div class="content">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        });

        // Close sidebar on link click (mobile)
        document.querySelectorAll('.sidebar-nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.getElementById('sidebar').classList.remove('active');
                }
            });
        });
    </script>
    @yield('scripts')
</body>
</html>
