@extends('layouts.app')

@section('title', 'Visitor Log')

@section('content')
<div class="page-header">
    <h1>Visitor Log</h1>
    <p>View all visitor records and registration history</p>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-filter"></i> Filters
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('attendance.visitor') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purpose</label>
                            <input type="text" name="purpose" class="form-control" placeholder="Search purpose..." value="{{ request('purpose') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="checked_in" {{ request('status') === 'checked_in' ? 'selected' : '' }}>Checked In</option>
                                <option value="checked_out" {{ request('status') === 'checked_out' ? 'selected' : '' }}>Checked Out</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="{{ route('attendance.visitor') }}" class="btn" style="background-color: #e0e0e0; color: #333;">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Visitor Log Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <i class="fas fa-table"></i> Visitor Records ({{ $visitors->total() }} total)
                </div>
                <form method="GET" action="{{ route('attendance.visitor.export') }}" style="display: inline;">
                    @foreach(request()->query() as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-download"></i> Export
                    </button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Person to Visit</th>
                                <th>Purpose</th>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($visitors as $visitor)
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">{{ $visitor->full_name }}</div>
                                        <div style="font-size: 12px; color: #999;">{{ $visitor->phone ?? '-' }}</div>
                                    </td>
                                    <td>{{ $visitor->phone ?? '-' }}</td>
                                    <td>{{ $visitor->person_to_visit_name ?? '-' }}</td>
                                    <td>
                                        <span style="font-size: 12px; background-color: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 3px;">
                                            {{ $visitor->purpose }}
                                        </span>
                                    </td>
                                    <td>{{ $visitor->date ? $visitor->date->format('M d, Y') : '-' }}</td>
                                   <td>
    {{ $visitor->time_in
        ? \Carbon\Carbon::parse($visitor->time_in)->format('h:i A')
        : '-'
    }}
</td>

<td>
    {{ $visitor->time_out
        ? \Carbon\Carbon::parse($visitor->time_out)->format('h:i A')
        : '-'
    }}
</td>

                                    <td>
                                        @if($visitor->time_out)
                                            <span style="background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">Checked Out</span>
                                        @else
                                            <span style="background-color: #cfe2ff; color: #084298; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">Checked In</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn" title="Edit" data-bs-toggle="modal" data-bs-target="#editVisitorModal" onclick="loadVisitor({{ $visitor->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 30px; color: #999;">
                                        <i class="fas fa-inbox"></i> No visitors found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px;">
                    {{ $visitors->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Visitor Modal -->
<div class="modal fade" id="editVisitorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Visitor Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editVisitorForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" id="visitor_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" id="visitor_phone" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Person to Visit</label>
                        <input type="text" name="person_to_visit" id="visitor_person" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Purpose of Visit *</label>
                        <input type="text" name="purpose" id="visitor_purpose" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date" id="visitor_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Check In Time *</label>
                            <input type="time" name="time_in" id="visitor_time_in" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Check Out Time</label>
                        <input type="time" name="time_out" id="visitor_time_out" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Visitor</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
async function loadVisitor(visitorId) {
    try {
        const response = await fetch(`/api/visitors/${visitorId}`);
        const visitor = await response.json();
        
        document.getElementById('visitor_name').value = visitor.full_name;
        document.getElementById('visitor_phone').value = visitor.phone || '';
        document.getElementById('visitor_person').value = visitor.person_to_visit || '';
        document.getElementById('visitor_purpose').value = visitor.purpose;
        document.getElementById('visitor_date').value = visitor.date;
        document.getElementById('visitor_time_in').value = visitor.time_in.substring(0, 5);
        document.getElementById('visitor_time_out').value = visitor.time_out ? visitor.time_out.substring(0, 5) : '';
        
        const form = document.getElementById('editVisitorForm');
        form.action = `/users/${visitorId}`;
    } catch (error) {
        console.error('Error loading visitor:', error);
        alert('Failed to load visitor data');
    }
}
</script>
@endsection