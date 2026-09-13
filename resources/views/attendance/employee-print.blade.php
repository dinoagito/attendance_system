<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance Report</title>
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
            font-size: 13px;
            color: #444;
        }

        .meta div {
            margin-bottom: 2px;
        }

        .employee-section {
            margin-bottom: 28px;
            page-break-inside: avoid;
        }

        .employee-section h2 {
            font-size: 15px;
            margin: 0 0 2px 0;
        }

        .employee-section .emeta {
            font-size: 12px;
            color: #444;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th, td {
            border: 1px solid #444;
            padding: 6px;
            font-size: 12px;
            text-align: left;
        }

        th {
            background: #f3f3f3;
            white-space: nowrap;
        }

        td.center {
            text-align: center;
        }

        .empty {
            color: #888;
        }

        .status-Present {
            font-weight: 700;
            color: #0a6c2f;
        }

        .status-Late {
            font-weight: 700;
            color: #8a6d00;
        }

        .status-Absent {
            font-weight: 700;
            color: #7a1212;
        }

        .status-Leave {
            font-weight: 700;
            color: #0b4d9a;
        }

        .status-Undertime {
            font-weight: 700;
            color: #444;
        }

        .actions {
            margin-top: 20px;
        }

        .actions button {
            padding: 8px 14px;
            margin-right: 8px;
            font-size: 13px;
            cursor: pointer;
        }

        .no-records {
            padding: 18px;
            border: 1px dashed #999;
            color: #777;
            font-size: 13px;
            text-align: center;
            margin-top: 6px;
        }

        .no-employees {
            padding: 40px;
            text-align: center;
            color: #777;
            font-size: 14px;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                margin: 0;
            }

            th, td {
                font-size: 11px;
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <h1>Employee Attendance Report</h1>
    <div class="meta">
        <div>Generated: {{ $generatedAt->format('M d, Y h:i A') }}</div>
        <div>Department: {{ !empty($filters['department']) && $filters['department'] !== 'all' ? $filters['department'] : 'All Departments' }}</div>
        <div>Employee: {{ !empty($filters['employee']) ? $filters['employee'] : 'All Employees' }}</div>
        <div>Date Range: {{ !empty($filters['date_from']) ? $filters['date_from'] : 'All dates' }}
            to {{ !empty($filters['date_to']) ? $filters['date_to'] : 'All dates' }}</div>
        <div>Total Employees: {{ $employees->count() }}</div>
    </div>

    @forelse($employees as $employee)
        @php
            $records = $employee->attendances;
            // Use the maximum number of populated time-in/time-out pairs
            // found across the employee's dates for the column count.
            $maxSlots = 0;
            foreach ($records as $rec) {
                $pairs = 0;
                if ($rec->time_in && $rec->time_out) $pairs++;
                if ($rec->time_in_2 && $rec->time_out_2) $pairs++;
                if ($rec->time_in_3 && $rec->time_out_3) $pairs++;
                if ($rec->time_in_4 && $rec->time_out_4) $pairs++;
                $maxSlots = max($maxSlots, $pairs);
            }
        @endphp

        <div class="employee-section">
            <h2>{{ $employee->full_name }}</h2>
            <div class="emeta">
                Employee No: {{ $employee->employee_id_number }}
                @if($employee->department)
                    &nbsp;|&nbsp; {{ $employee->department }}
                @endif
                &nbsp;|&nbsp; Records: {{ $records->count() }}
            </div>

            @if($records->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            @for($i = 1; $i <= $maxSlots; $i++)
                                <th>Time In</th>
                                <th>Time Out</th>
                            @endfor
                            <th>Total Hours</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $record)
                            @php
                                $slotPairs = [
                                    1 => [$record->time_in, $record->time_out],
                                    2 => [$record->time_in_2, $record->time_out_2],
                                    3 => [$record->time_in_3, $record->time_out_3],
                                    4 => [$record->time_in_4, $record->time_out_4],
                                ];
                                $totalMinutes = 0;
                                foreach ($slotPairs as $pair) {
                                    if ($pair[0] && $pair[1]) {
                                        $in = \Carbon\Carbon::parse($pair[0]);
                                        $out = \Carbon\Carbon::parse($pair[1]);
                                        if ($out->gt($in)) {
                                            $totalMinutes += $in->diffInMinutes($out);
                                        }
                                    }
                                }
                                $totalHrs = intdiv($totalMinutes, 60);
                                $totalMin = $totalMinutes % 60;
                                $totalLabel = $totalMinutes > 0
                                    ? $totalHrs . 'h ' . str_pad($totalMin, 2, '0', STR_PAD_LEFT) . 'm'
                                    : '-';
                            @endphp
                            <tr>
                                <td>{{ $record->date->format('M d, Y') }} ({{ $record->date->format('l') }})</td>
                                @for($i = 1; $i <= $maxSlots; $i++)
                                    <td class="center">
                                        @if($slotPairs[$i][0])
                                            {{ date('h:i A', strtotime($slotPairs[$i][0])) }}
                                        @else
                                            <span class="empty">--</span>
                                        @endif
                                    </td>
                                    <td class="center">
                                        @if($slotPairs[$i][1])
                                            {{ date('h:i A', strtotime($slotPairs[$i][1])) }}
                                        @else
                                            <span class="empty">--</span>
                                        @endif
                                    </td>
                                @endfor
                                <td class="center">{{ $totalLabel }}</td>
                                <td class="center status-{{ $record->status ?? '' }}">{{ $record->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="no-records">No attendance records in the selected date range.</div>
            @endif
        </div>
    @empty
        <div class="no-employees">No employees match the selected filters.</div>
    @endforelse

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