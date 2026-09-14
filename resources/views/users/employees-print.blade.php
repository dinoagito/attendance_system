<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            color: #111;
        }

        h1 {
            margin-bottom: 8px;
        }

        .meta {
            margin-bottom: 16px;
            font-size: 14px;
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th, td {
            border: 1px solid #444;
            padding: 8px;
            font-size: 13px;
            text-align: left;
        }

        th {
            background: #f3f3f3;
        }

        .status-active {
            color: #0a6c2f;
            font-weight: 700;
        }

        .status-inactive {
            color: #7a1212;
            font-weight: 700;
        }

        .actions {
            margin-top: 20px;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <h1>Employee List</h1>
    <div class="meta">
        Generated: {{ now()->format('M d, Y h:i A') }}<br>
        Status Filter: {{ $employeeFilters['status'] ? ucfirst($employeeFilters['status']) : 'All' }}<br>
        Search Keyword: {{ $employeeFilters['search'] ?: 'None' }}<br>
        Total Records: {{ $employees->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Employee ID</th>
                <th>Department</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
                <tr>
                    <td>{{ $employee->full_name }}</td>
                    <td>{{ $employee->employee_id_number }}</td>
                    <td>{{ $employee->department ?? '-' }}</td>
                    <td class="status-{{ $employee->status === 'inactive' ? 'inactive' : 'active' }}">
                        {{ ucfirst($employee->status ?? 'active') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center;">No employees found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="actions">
        <button onclick="window.print();">Print / Save as PDF</button>
        <button onclick="window.close();">Close</button>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 400);
        });
    </script>
</body>
</html>
