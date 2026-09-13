@extends('layouts.app')

@section('title', 'Schedule Management')

@section('content')
<div class="page-header">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1>Schedule Management - Employees / Faculty</h1>
            <p>View and manage work schedules for employees and faculty</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Registration Management
        </a>
    </div>
</div>

<!-- Filter Alert Section -->
@if($filterEmployeeId && $filteredEmployee)
<div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong><i class="fas fa-filter"></i> Filtered View:</strong> Showing schedules for 
            <strong>{{ $filteredEmployee->full_name }}</strong> ({{ $filteredEmployee->employee_id_number }})
        </div>
        <a href="{{ route('schedule.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-times"></i> Display All
        </a>
    </div>
</div>
@endif

<div class="row mb-4">
    <div class="col-12">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
            <i class="fas fa-plus"></i> Add New Schedule
        </button>
    </div>
</div>

<!-- Schedules Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar"></i> Work Schedules
                @if($filterEmployeeId)
                    <span class="badge bg-info ms-2">{{ $schedules->count() }} schedule(s) for {{ $filteredEmployee->full_name }}</span>
                @else
                    <span class="badge bg-secondary ms-2">{{ $schedules->count() }} total schedule(s)</span>
                @endif
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Days</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Hours</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            @forelse($schedules as $schedule)
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">
                                            @if($schedule->employee)
                                                {{ $schedule->employee->full_name }}
                                                <div style="font-size: 12px; color: #999;">{{ $schedule->employee->employee_id_number }} - {{ $schedule->employee->department ?? 'No Department' }}</div>
                                            @else
                                                <span style="color: #999;">Unassigned Template</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 500;">{{ $schedule->days_display }}</span>
                                        <div style="font-size: 11px; color: #666;">{{ count($schedule->days) }} day(s)</div>
                                    </td>
                                    <td>{{ date('h:i A', strtotime($schedule->start_time)) }}</td>
                                    <td>{{ date('h:i A', strtotime($schedule->end_time)) }}</td>
                                    <td>
                                        @php
                                            $start = \Carbon\Carbon::parse($schedule->start_time);
                                            $end = \Carbon\Carbon::parse($schedule->end_time);
                                            $hours = abs($end->diffInHours($start, false));
                                        @endphp
                                        {{ $hours }} hour{{ $hours !== 1 ? 's' : '' }}
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button
                                                class="action-btn"
                                                title="Edit"
                                                onclick="openEditSchedule(this)"
                                                data-id="{{ $schedule->ids[0] }}"
                                                data-employee-id="{{ $schedule->employee_id }}"
                                                data-days='@json($schedule->days)'
                                                data-start-time="{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}"
                                                data-end-time="{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="action-btn delete" title="Delete" onclick="deleteSchedule({{ $schedule->ids[0] }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                                        <i class="fas fa-inbox"></i> No schedules found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Schedule - Employee / Faculty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('schedule.store') }}" id="addScheduleForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Employee / Faculty *</label>
                        <input type="text" id="employeeSearch" class="form-control mb-2" placeholder="Search employee by name or ID">
                        <select name="employee_id" id="employeeSelect" class="form-control @error('employee_id') is-invalid @enderror" required>
                            <option value="">Select Employee / Faculty</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_id_number }}) - {{ $employee->department ?? 'No Department' }}</option>
                            @endforeach
                        </select>
                        @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Days of Week *</label>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyDayPreset('weekdays')">Weekdays</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyDayPreset('weekend')">Weekend</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyDayPreset('all')">All Days</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="applyDayPreset('clear')">Clear</button>
                        </div>
                        <div>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                @php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; @endphp
                                @foreach($days as $day)
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <input type="checkbox" name="schedule_days[]" value="{{ $day }}" id="day{{ $loop->index }}" class="add-day-checkbox">
                                        <label for="day{{ $loop->index }}" style="margin-bottom: 0;">{{ $day }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @error('schedule_days') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time In *</label>
                            <input type="time" name="schedule_start_time" id="addStartTime" class="form-control @error('schedule_start_time') is-invalid @enderror" value="08:00" step="900" required>
                            @error('schedule_start_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time Out *</label>
                            <input type="time" name="schedule_end_time" id="addEndTime" class="form-control @error('schedule_end_time') is-invalid @enderror" value="17:00" step="900" required>
                            @error('schedule_end_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div id="scheduleConflictWarning" class="alert alert-warning" style="display: none;"></div>

                    <div class="card" id="schedulePreviewCard" style="background: #f8fbff; border: 1px solid #dce8f5;">
                        <div class="card-body" style="padding: 12px 14px;">
                            <div style="font-size: 12px; color: #6c757d; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase;">Live Preview</div>
                            <div id="schedulePreviewText" style="margin-top: 6px; color: #2c3e50;">Select employee, days, and time to preview schedule summary.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal fade" id="editScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editScheduleForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="editScheduleRecordId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Employee / Faculty *</label>
                        <input type="text" id="editEmployeeSearch" class="form-control mb-2" placeholder="Search employee by name or ID">
                        <select name="employee_id" id="editEmployeeSelect" class="form-control" required>
                            <option value="">Select Employee / Faculty</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }} ({{ $employee->employee_id_number }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Days of Week *</label>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                            @php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; @endphp
                            @foreach($days as $day)
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="checkbox" name="schedule_days[]" value="{{ $day }}" id="editDay{{ $loop->index }}" class="edit-day-checkbox">
                                    <label for="editDay{{ $loop->index }}" style="margin-bottom: 0;">{{ $day }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time In *</label>
                            <input type="time" name="schedule_start_time" id="editStartTime" class="form-control" step="900" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Time Out *</label>
                            <input type="time" name="schedule_end_time" id="editEndTime" class="form-control" step="900" required>
                        </div>
                    </div>

                    <div id="editScheduleConflictWarning" class="alert alert-warning" style="display: none;"></div>

                    <div class="card" style="background: #f8fbff; border: 1px solid #dce8f5;">
                        <div class="card-body" style="padding: 12px 14px;">
                            <div style="font-size: 12px; color: #6c757d; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase;">Live Preview</div>
                            <div id="editSchedulePreviewText" style="margin-top: 6px; color: #2c3e50;">Select employee, days, and time to preview schedule summary.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

@php
    $existingScheduleRules = $schedules->map(function ($schedule) {
        return [
            'id' => $schedule->ids[0] ?? null,
            'employee_id' => $schedule->employee_id,
            'days' => $schedule->days,
            'start_time' => \Carbon\Carbon::parse($schedule->start_time)->format('H:i'),
            'end_time' => \Carbon\Carbon::parse($schedule->end_time)->format('H:i'),
        ];
    })->values();
@endphp

<script>
const existingScheduleRules = @json($existingScheduleRules);
const searchableOptionsCache = {};

function cacheSelectOptions(selectId) {
    const select = document.getElementById(selectId);
    if (!select || searchableOptionsCache[selectId]) {
        return;
    }

    searchableOptionsCache[selectId] = Array.from(select.options)
        .filter(option => option.value !== '')
        .map(option => ({ value: option.value, text: option.text }));
}

function rebuildSelectOptions(selectId, query) {
    const select = document.getElementById(selectId);
    const options = searchableOptionsCache[selectId] || [];
    const placeholderText = 'Select Employee / Faculty';
    const currentValue = select.value;
    const normalizedQuery = (query || '').trim().toLowerCase();

    select.innerHTML = '';
    select.appendChild(new Option(placeholderText, ''));

    const filtered = normalizedQuery
        ? options.filter(option => option.text.toLowerCase().includes(normalizedQuery))
        : options;

    filtered.forEach(option => {
        select.appendChild(new Option(option.text, option.value));
    });

    if (filtered.some(option => option.value === currentValue)) {
        select.value = currentValue;
    } else {
        select.value = '';
    }
}

function setupSearchableSelect(searchInputId, selectId) {
    cacheSelectOptions(selectId);

    const searchInput = document.getElementById(searchInputId);
    if (!searchInput) {
        return;
    }

    searchInput.addEventListener('input', function() {
        rebuildSelectOptions(selectId, searchInput.value);
        refreshAddScheduleInsights();
    });
}

function parseTimeToMinutes(timeValue) {
    if (!timeValue || !timeValue.includes(':')) {
        return null;
    }

    const [hours, minutes] = timeValue.split(':').map(Number);
    return (hours * 60) + minutes;
}

function formatSelectedDays(days) {
    const dayMap = {
        Monday: 'Mon',
        Tuesday: 'Tue',
        Wednesday: 'Wed',
        Thursday: 'Thu',
        Friday: 'Fri',
        Saturday: 'Sat',
        Sunday: 'Sun'
    };

    if (days.length === 0) {
        return 'No days selected';
    }

    return days.map(day => dayMap[day] || day).join(', ');
}

function formatTime12h(timeValue) {
    if (!timeValue) {
        return '--:--';
    }

    const [hourText, minute] = timeValue.split(':');
    let hour = Number(hourText);
    const period = hour >= 12 ? 'PM' : 'AM';
    hour = hour % 12;
    if (hour === 0) hour = 12;
    return `${String(hour).padStart(2, '0')}:${minute} ${period}`;
}

function getSelectedAddDays() {
    return Array.from(document.querySelectorAll('.add-day-checkbox:checked')).map(input => input.value);
}

function applyDayPreset(preset) {
    const presets = {
        weekdays: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
        weekend: ['Saturday', 'Sunday'],
        all: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        clear: []
    };

    const selectedDays = presets[preset] || [];
    document.querySelectorAll('.add-day-checkbox').forEach(checkbox => {
        checkbox.checked = selectedDays.includes(checkbox.value);
    });

    refreshAddScheduleInsights();
}

function refreshAddScheduleInsights() {
    const employeeSelect = document.getElementById('employeeSelect');
    const selectedDays = getSelectedAddDays();
    const startTime = document.getElementById('addStartTime').value;
    const endTime = document.getElementById('addEndTime').value;

    const selectedUserName = employeeSelect.options[employeeSelect.selectedIndex]?.text || 'No employee selected';

    const preview = document.getElementById('schedulePreviewText');
    const startMinutes = parseTimeToMinutes(startTime);
    const endMinutes = parseTimeToMinutes(endTime);
    const durationHours = (startMinutes !== null && endMinutes !== null && endMinutes > startMinutes)
        ? ((endMinutes - startMinutes) / 60)
        : null;

    preview.textContent = `Employee: ${selectedUserName} | Days: ${formatSelectedDays(selectedDays)} | Time: ${formatTime12h(startTime)} - ${formatTime12h(endTime)}${durationHours ? ` | Duration: ${durationHours}h` : ''}`;

    const warning = document.getElementById('scheduleConflictWarning');
    warning.style.display = 'none';
    warning.textContent = '';

    const selectedUserId = employeeSelect.value;
    if (!selectedUserId || selectedDays.length === 0 || startMinutes === null || endMinutes === null || endMinutes <= startMinutes) {
        return;
    }

    const conflicts = [];
    existingScheduleRules.forEach(rule => {
        if (String(rule.employee_id || '') !== selectedUserId) {
            return;
        }

        const overlappingDays = selectedDays.filter(day => (rule.days || []).includes(day));
        if (overlappingDays.length === 0) {
            return;
        }

        const ruleStart = parseTimeToMinutes(rule.start_time);
        const ruleEnd = parseTimeToMinutes(rule.end_time);
        const isTimeOverlap = startMinutes < ruleEnd && endMinutes > ruleStart;

        if (isTimeOverlap) {
            conflicts.push({
                days: overlappingDays,
                start: rule.start_time,
                end: rule.end_time
            });
        }
    });

    if (conflicts.length > 0) {
        const details = conflicts.map(conflict => `${formatSelectedDays(conflict.days)} (${formatTime12h(conflict.start)} - ${formatTime12h(conflict.end)})`).join('; ');
        warning.textContent = `Possible schedule conflict detected for this employee: ${details}`;
        warning.style.display = 'block';
    }
}

function getSelectedEditDays() {
    return Array.from(document.querySelectorAll('.edit-day-checkbox:checked')).map(input => input.value);
}

function refreshEditScheduleInsights() {
    const employeeSelect = document.getElementById('editEmployeeSelect');
    const selectedDays = getSelectedEditDays();
    const startTime = document.getElementById('editStartTime').value;
    const endTime = document.getElementById('editEndTime').value;
    const currentScheduleId = document.getElementById('editScheduleRecordId').value;

    const selectedUserName = employeeSelect.options[employeeSelect.selectedIndex]?.text || 'No employee selected';

    const preview = document.getElementById('editSchedulePreviewText');
    const startMinutes = parseTimeToMinutes(startTime);
    const endMinutes = parseTimeToMinutes(endTime);
    const durationHours = (startMinutes !== null && endMinutes !== null && endMinutes > startMinutes)
        ? ((endMinutes - startMinutes) / 60)
        : null;

    preview.textContent = `Employee: ${selectedUserName} | Days: ${formatSelectedDays(selectedDays)} | Time: ${formatTime12h(startTime)} - ${formatTime12h(endTime)}${durationHours ? ` | Duration: ${durationHours}h` : ''}`;

    const warning = document.getElementById('editScheduleConflictWarning');
    warning.style.display = 'none';
    warning.textContent = '';

    const selectedUserId = employeeSelect.value;
    if (!selectedUserId || selectedDays.length === 0 || startMinutes === null || endMinutes === null || endMinutes <= startMinutes) {
        return;
    }

    const conflicts = [];
    existingScheduleRules.forEach(rule => {
        if (String(rule.id || '') === String(currentScheduleId || '')) {
            return;
        }

        if (String(rule.employee_id || '') !== selectedUserId) {
            return;
        }

        const overlappingDays = selectedDays.filter(day => (rule.days || []).includes(day));
        if (overlappingDays.length === 0) {
            return;
        }

        const ruleStart = parseTimeToMinutes(rule.start_time);
        const ruleEnd = parseTimeToMinutes(rule.end_time);
        const isTimeOverlap = startMinutes < ruleEnd && endMinutes > ruleStart;

        if (isTimeOverlap) {
            conflicts.push({
                days: overlappingDays,
                start: rule.start_time,
                end: rule.end_time
            });
        }
    });

    if (conflicts.length > 0) {
        const details = conflicts.map(conflict => `${formatSelectedDays(conflict.days)} (${formatTime12h(conflict.start)} - ${formatTime12h(conflict.end)})`).join('; ');
        warning.textContent = `Possible schedule conflict detected for this employee: ${details}`;
        warning.style.display = 'block';
    }
}

function openEditSchedule(button) {
    const id = button.dataset.id;
    const employeeId = button.dataset.employeeId;
    const days = JSON.parse(button.dataset.days || '[]');
    const startTime = button.dataset.startTime;
    const endTime = button.dataset.endTime;

    const form = document.getElementById('editScheduleForm');
    form.action = `/schedule/${id}`;
    document.getElementById('editScheduleRecordId').value = id;

    document.getElementById('editEmployeeSelect').value = employeeId || '';

    document.querySelectorAll('input[name="schedule_days[]"][id^="editDay"]').forEach(checkbox => {
        checkbox.checked = days.includes(checkbox.value);
    });

    document.getElementById('editStartTime').value = startTime;
    document.getElementById('editEndTime').value = endTime;

    refreshEditScheduleInsights();

    bootstrap.Modal.getOrCreateInstance(document.getElementById('editScheduleModal')).show();
}

function deleteSchedule(scheduleId) {
    if (confirm('Are you sure you want to delete this schedule?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/schedule/' + scheduleId;
        form.innerHTML = '@csrf @method("DELETE")';
        document.body.appendChild(form);
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    setupSearchableSelect('employeeSearch', 'employeeSelect');
    setupSearchableSelect('editEmployeeSearch', 'editEmployeeSelect');

    const addFormElements = [
        document.getElementById('employeeSelect'),
        document.getElementById('addStartTime'),
        document.getElementById('addEndTime')
    ];

    addFormElements.forEach(element => {
        if (element) {
            element.addEventListener('change', refreshAddScheduleInsights);
            element.addEventListener('input', refreshAddScheduleInsights);
        }
    });

    document.querySelectorAll('.add-day-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', refreshAddScheduleInsights);
    });

    const editFormElements = [
        document.getElementById('editEmployeeSelect'),
        document.getElementById('editStartTime'),
        document.getElementById('editEndTime')
    ];

    editFormElements.forEach(element => {
        if (element) {
            element.addEventListener('change', refreshEditScheduleInsights);
            element.addEventListener('input', refreshEditScheduleInsights);
        }
    });

    document.querySelectorAll('.edit-day-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', refreshEditScheduleInsights);
    });

    refreshAddScheduleInsights();
    refreshEditScheduleInsights();
});
</script>

@endsection
