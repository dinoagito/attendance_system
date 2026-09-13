@extends('layouts.public')

@section('title', 'Register Visitor')

@section('content')
<div class="page-header">
    <h1>Visitor Registration</h1>
    <p>Register new visitor or check-in existing visitor</p>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-circle-check"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Please fix the following:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <!-- Registration Form -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-user-plus"></i> New Visitor Registration
            </div>
            <div class="card-body">
                <form id="visitorForm" method="POST" action="{{ route('visitor.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" placeholder="Enter visitor name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" name="phone" placeholder="Enter phone number" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Person to Visit *</label>
                        <select class="form-control" name="person_to_visit" required>
                            <option selected disabled value="">Select person to visit</option>
                            @forelse($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }} - {{ $employee->department }}</option>
                            @empty
                                <option disabled>No employees found</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Purpose of Visit *</label>
                        <select class="form-control" name="purpose" required>
                            <option selected disabled value="">Select purpose</option>
                            <option>Meeting</option>
                            <option>Delivery</option>
                            <option>Maintenance</option>
                            <option>Business</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea class="form-control" name="remarks" rows="2" placeholder="Additional remarks..."></textarea>
                    </div>

                    <input type="hidden" id="photoInput" name="photo_base64">

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> Complete Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Photo Capture -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-camera"></i> Photo Upload/Capture
            </div>
            <div class="card-body" style="text-align: center;">
                <div id="uploadPlaceholder" style="background-color: #ecf0f1; padding: 40px 20px; border-radius: 8px; margin-bottom: 20px; border: 2px dashed #3498db;">
                    <div style="margin-bottom: 20px;">
                        <i class="fas fa-image" style="font-size: 60px; color: #999;"></i>
                    </div>
                    <p style="color: #7f8c8d; margin-bottom: 10px;">No photo uploaded</p>
                    <small style="color: #999;">Click below to upload or drag & drop</small>
                </div>

                <div class="btn-group mb-3" style="width: 100%;">
                    <label style="display: block; margin-bottom: 10px; flex: 1;">
                        <input type="file" id="photoFile" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('photoFile').click()" style="width: 100%;">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </label>
                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="startWebcam()" style="width: 100%;" id="webcamBtn">
                    <i class="fas fa-camera"></i> Capture with Webcam
                </button>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
                    <p style="color: #7f8c8d; font-size: 12px; margin-bottom: 0;">
                        <i class="fas fa-info-circle"></i>
                        Supported formats: JPG, PNG, BMP (Max 5MB)
                    </p>
                </div>
            </div>
        </div>

        <!-- Photo Preview -->
        <div class="card" id="previewCard" style="display: none;">
            <div class="card-header">
                <i class="fas fa-image"></i> Photo Preview
            </div>
            <div class="card-body" style="text-align: center;">
                <img id="photoPreview" src="" alt="Preview" style="width: 100%; max-width: 200px; border-radius: 8px; margin-bottom: 15px;">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePhoto()">
                    <i class="fas fa-trash"></i> Remove Photo
                </button>
            </div>
        </div>

        <!-- Success Modal -->
        <div class="modal fade" id="visitorSuccessModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-circle-check text-success"></i> Visitor Registered</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <div class="mb-3" style="font-size: 56px; color: #198754;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h5 id="visitorSuccessTitle" class="mb-2">Registration successful</h5>
                        <p id="visitorSuccessMessage" class="mb-0 text-muted">--</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checkout Success Modal -->
        <div class="modal fade" id="checkoutSuccessModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-circle-check text-success"></i> Visitor Checked Out</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <div class="mb-3" style="font-size: 56px; color: #198754;">
                        </div>
                        <h5 class="mb-2">Visitor checked out successfully.</h5>
                        <p id="checkoutSuccessMessage" class="mb-0 text-muted">--</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webcam Capture Modal -->
        <div class="modal fade" id="webcamModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Capture Photo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" style="text-align: center;">
                        <video id="webcamVideo" autoplay playsinline muted style="width: 100%; max-width: 400px; min-height: 260px; object-fit: cover; background: #111; border-radius: 8px; margin: 0 auto 20px; display: none; transform: scaleX(-1);"></video>
                        <canvas id="photoCanvas" style="display: none;"></canvas>
                        <div id="webcamInitializing">
                            <p><i class="fas fa-spinner fa-spin"></i> Initializing camera...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="capturePhoto()" id="captureBtn" style="display: none;">
                            <i class="fas fa-camera"></i> Capture
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Visitor Records -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Recent Visitor Records (Today: <span id="visitorCount">0</span> visitors)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Person to Visit</th>
                                <th>Purpose</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Duration</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="visitorsTableBody">
                            @forelse($todaysVisitors as $visitor)
                                <tr id="visitor-row-{{ $visitor->id }}">
                                    <td>
                                        @if($visitor->photo_url)
                                            <img src="{{ $visitor->photo_url }}" alt="Photo" class="profile-pic">
                                        @else
                                            <div class="profile-pic" style="background-color: #ddd; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user"></i></div>
                                        @endif
                                    </td>
                                    <td>{{ $visitor->full_name }}</td>
                                    <td>{{ $visitor->phone }}</td>
                                    <td>{{ $visitor->person_to_visit_name }}</td>
                                    <td><span class="badge-purpose badge-{{ strtolower($visitor->purpose) }}">{{ $visitor->purpose }}</span></td>
                                    <td>{{ $visitor->check_in_time }}</td>
                                    <td>{{ $visitor->check_out_time }}</td>
                                    <td>{{ $visitor->duration }}</td>
                                    <td>
                                        <div class="action-buttons">
                                            @if($visitor->time_out)
                                                <button class="action-btn" title="View" onclick="viewVisitor({{ $visitor->id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    class="action-btn"
                                                    title="Check Out"
                                                    onclick="checkOutVisitor({{ $visitor->id }})"
                                                >
                                                    <i class="fas fa-sign-out-alt"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin"></i> Loading visitors...
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

<style>
    .profile-pic {
        width: 40px;
        height: 40px;
        border-radius: 4px;
        object-fit: cover;
    }

    .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
    }

    .action-btn {
        background: none;
        border: none;
        color: #3498db;
        cursor: pointer;
        padding: 5px 8px;
        font-size: 14px;
        transition: color 0.3s;
    }

    .action-btn:hover {
        color: #2980b9;
    }

    .badge-purpose {
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 3px;
        font-weight: 500;
    }

    .badge-meeting { background-color: #e3f2fd; color: #1976d2; }
    .badge-delivery { background-color: #f3e5f5; color: #7b1fa2; }
    .badge-maintenance { background-color: #e8f5e9; color: #388e3c; }
    .badge-business { background-color: #fff3e0; color: #e65100; }
    .badge-other { background-color: #f0f0f0; color: #666; }

    #visitorForm .form-control:focus,
    #visitorForm .form-select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }

    #webcamVideo {
        transform: scaleX(-1);
    }
</style>

<script>
    let currentPhoto = null;
    let webcamStream = null;
    const visitorSuccessModalEl = document.getElementById('visitorSuccessModal');
    const visitorSuccessModal = visitorSuccessModalEl ? bootstrap.Modal.getOrCreateInstance(visitorSuccessModalEl) : null;
    const checkoutSuccessModalEl = document.getElementById('checkoutSuccessModal');
    const checkoutSuccessModal = checkoutSuccessModalEl ? bootstrap.Modal.getOrCreateInstance(checkoutSuccessModalEl) : null;

    function showVisitorSuccess(message, title = 'Registration successful') {
        if (!visitorSuccessModal) {
            alert(message || 'Visitor registered successfully!');
            return;
        }

        document.getElementById('visitorSuccessTitle').textContent = title;
        document.getElementById('visitorSuccessMessage').textContent = message || 'Visitor registered successfully.';
        visitorSuccessModal.show();
    }

    @if (session('success'))
    document.addEventListener('DOMContentLoaded', function() {
        showVisitorSuccess(@json(session('success')));
    });
    @endif

    // Handle file upload
    document.getElementById('photoFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                displayPhotoPreview(event.target.result);
                currentPhoto = event.target.result;
                document.getElementById('photoInput').value = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Handle drag and drop
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    uploadPlaceholder.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadPlaceholder.style.backgroundColor = '#d5e8f7';
    });

    uploadPlaceholder.addEventListener('dragleave', () => {
        uploadPlaceholder.style.backgroundColor = '#ecf0f1';
    });

    uploadPlaceholder.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadPlaceholder.style.backgroundColor = '#ecf0f1';
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(event) {
                displayPhotoPreview(event.target.result);
                currentPhoto = event.target.result;
                document.getElementById('photoInput').value = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    function displayPhotoPreview(src) {
        document.getElementById('uploadPlaceholder').style.display = 'none';
        document.getElementById('previewCard').style.display = 'block';
        document.getElementById('photoPreview').src = src;
    }

    function removePhoto() {
        currentPhoto = null;
        document.getElementById('photoInput').value = '';
        document.getElementById('uploadPlaceholder').style.display = 'block';
        document.getElementById('previewCard').style.display = 'none';
        document.getElementById('photoFile').value = '';
    }

    function dataUrlToFile(dataUrl, filename) {
        const arr = dataUrl.split(',');
        const mimeMatch = arr[0].match(/:(.*?);/);
        const mime = mimeMatch ? mimeMatch[1] : 'image/jpeg';
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);

        while (n--) {
            u8arr[n] = bstr.charCodeAt(n);
        }

        return new File([u8arr], filename, { type: mime });
    }

    function stopWebcam() {
        if (webcamStream) {
            webcamStream.getTracks().forEach(track => track.stop());
            webcamStream = null;
        }

        const video = document.getElementById('webcamVideo');
        if (video) {
            video.pause();
            video.srcObject = null;
            video.style.display = 'none';
        }
    }

    async function startWebcam() {
        const modalElement = document.getElementById('webcamModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        const video = document.getElementById('webcamVideo');
        const initializing = document.getElementById('webcamInitializing');
        const captureBtn = document.getElementById('captureBtn');

        initializing.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Initializing camera...</p>';
        initializing.style.display = 'block';
        captureBtn.style.display = 'none';
        video.style.display = 'none';

        modal.show();

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            initializing.innerHTML = '<p style="color: red;"><i class="fas fa-times-circle"></i> Camera is not supported in this browser</p>';
            return;
        }

        try {
            stopWebcam();

            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user' },
                audio: false
            });

            webcamStream = stream;
            video.srcObject = stream;
            video.style.transform = 'scaleX(-1)';
            video.onloadedmetadata = function() {
                video.play().catch(() => {});
            };

            video.style.display = 'block';
            initializing.style.display = 'none';
            captureBtn.style.display = 'block';
        } catch (error) {
            initializing.innerHTML = '<p style="color: red;"><i class="fas fa-times-circle"></i> Unable to access camera. Allow camera permission and try again.</p>';
            console.error('Camera error:', error);
        }
    }

    function capturePhoto() {
        const video = document.getElementById('webcamVideo');
        const canvas = document.getElementById('photoCanvas');
        const context = canvas.getContext('2d');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        // Mirror the captured frame to match the mirrored webcam preview.
        context.save();
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
        context.drawImage(video, 0, 0);
        context.restore();

        const photoData = canvas.toDataURL('image/jpeg');
        displayPhotoPreview(photoData);
        currentPhoto = photoData;
        document.getElementById('photoInput').value = photoData;

        // Stop the webcam
        stopWebcam();

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('webcamModal'));
        modal.hide();
    }

    document.getElementById('webcamModal').addEventListener('hidden.bs.modal', function() {
        stopWebcam();
        document.getElementById('captureBtn').style.display = 'none';
        document.getElementById('webcamInitializing').style.display = 'block';
    });

    // Load today's visitors
    function loadTodaysVisitors() {
        fetch('{{ route("visitor.index") }}?_=' + Date.now(), {
            headers: {
                'Accept': 'application/json'
            },
            cache: 'no-store'
        })
            .then(async response => {
                let data = null;
                try {
                    data = await response.json();
                } catch (parseError) {
                    throw new Error('Server returned an invalid visitors response.');
                }

                if (!response.ok) {
                    throw new Error(data?.message || 'Error loading visitors.');
                }

                renderVisitorTable(data);
            })
            .catch(error => {
                console.error('Error loading visitors:', error);
                document.getElementById('visitorsTableBody').innerHTML = '<tr><td colspan="9" class="text-center text-danger"><i class="fas fa-exclamation-circle"></i> Error loading visitors</td></tr>';
            });
    }

    function prependVisitorRow(visitor) {
        const tbody = document.getElementById('visitorsTableBody');
        if (!tbody) return;

        const visitorRow = renderVisitorRow(visitor);

        const noRowsMessage = tbody.querySelector('tr td[colspan]');
        if (noRowsMessage) {
            tbody.innerHTML = visitorRow;
            return;
        }

        tbody.insertAdjacentHTML('afterbegin', visitorRow);
    }

    function renderVisitorRow(visitor) {
        return `
            <tr id="visitor-row-${visitor.id}">
                <td>
                    ${visitor.photo_path ? `<img src="${visitor.photo_path}" alt="Photo" class="profile-pic">` : '<div class="profile-pic" style="background-color: #ddd; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user"></i></div>'}
                </td>
                <td>${visitor.full_name || '-'}</td>
                <td>${visitor.phone || '-'}</td>
                <td>${visitor.person_to_visit_name || 'N/A'}</td>
                <td><span class="badge-purpose badge-${String(visitor.purpose || 'other').toLowerCase()}">${visitor.purpose || 'Other'}</span></td>
                <td class="visitor-time-in">${visitor.time_in ? formatTime(visitor.time_in) : '-'}</td>
                <td class="visitor-time-out">${visitor.time_out ? formatTime(visitor.time_out) : '--'}</td>
                <td class="visitor-duration">${visitor.duration || '-'}</td>
                <td>
                    <div class="action-buttons">
                        ${visitor.time_out ? `
                            <button type="button" class="action-btn" title="View" onclick="viewVisitor(${visitor.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        ` : `
                            <button
                                type="button"
                                class="action-btn"
                                title="Check Out"
                                onclick="checkOutVisitor(${visitor.id})"
                            >
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        `}
                    </div>
                </td>
            </tr>
        `;
    }

    function updateVisitorRow(visitor) {
        const tbody = document.getElementById('visitorsTableBody');
        if (!tbody || !visitor || !visitor.id) return;

        const existingRow = document.getElementById(`visitor-row-${visitor.id}`);
        const rowHtml = renderVisitorRow(visitor);

        if (existingRow) {
            existingRow.outerHTML = rowHtml;
            return;
        }
    }

    function renderVisitorTable(visitors) {
        const tbody = document.getElementById('visitorsTableBody');
        document.getElementById('visitorCount').textContent = visitors.length;

        if (visitors.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No visitors registered today</td></tr>';
            return;
        }

        tbody.innerHTML = visitors.map(visitor => `
            <tr id="visitor-row-${visitor.id}">
                <td>
                    ${visitor.photo_path ? `<img src="${visitor.photo_path}" alt="Photo" class="profile-pic">` : '<div class="profile-pic" style="background-color: #ddd; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user"></i></div>'}
                </td>
                <td>${visitor.full_name}</td>
                <td>${visitor.phone}</td>
                <td>${visitor.person_to_visit_name || 'N/A'}</td>
                <td><span class="badge-purpose badge-${visitor.purpose.toLowerCase()}">${visitor.purpose}</span></td>
                <td>${formatTime(visitor.time_in)}</td>
                <td>${visitor.time_out ? formatTime(visitor.time_out) : '--'}</td>
                <td>${getDuration(visitor.time_in, visitor.time_out)}</td>
                <td>
                    <div class="action-buttons">
                        ${visitor.time_out ? `
                            <button type="button" class="action-btn" title="View" onclick="viewVisitor(${visitor.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        ` : `
                            <button
                                type="button"
                                class="action-btn"
                                title="Check Out"
                                onclick="checkOutVisitor(${visitor.id})"
                            >
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        `}
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function formatTime(time) {
        if (!time) return '-';
        // time is in HH:mm:ss format
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const min = parseInt(minutes);
        const period = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${String(displayHour).padStart(2, '0')}:${String(min).padStart(2, '0')} ${period}`;
    }

    function getDuration(timeIn, timeOut) {
        if (!timeIn) return '-';
        
        // Parse HH:mm:ss format
        const [inHours, inMinutes, inSeconds] = timeIn.split(':').map(Number);
        const inTime = inHours * 3600 + inMinutes * 60 + inSeconds;
        
        let outTime;
        if (timeOut) {
            const [outHours, outMinutes, outSeconds] = timeOut.split(':').map(Number);
            outTime = outHours * 3600 + outMinutes * 60 + outSeconds;
        } else {
            // Use current time
            const now = new Date();
            outTime = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();
        }
        
        // Handle day change
        let diff = outTime - inTime;
        if (diff < 0) {
            diff += 86400; // Add 24 hours
        }

        const hours = Math.floor(diff / 3600);
        const minutes = Math.floor((diff % 3600) / 60);

        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            return `${minutes}m`;
        } else {
            return `${diff}s`;
        }
    }

    function showCheckoutSuccessModal(message) {
        const modalElement = document.getElementById('checkoutSuccessModal');
        const messageElement = document.getElementById('checkoutSuccessMessage');

        if (!modalElement || !messageElement || !window.bootstrap || !bootstrap.Modal) {
            alert(message || 'Visitor checked out successfully.');
            return;
        }

        messageElement.textContent = message || 'Visitor checked out successfully.';
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }

    function checkOutVisitor(id) {
        const btn = document.querySelector(`#visitor-row-${id} button[onclick*="checkOutVisitor"]`);
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.style.opacity = '0.6';
            btn.style.pointerEvents = 'none';
        }
        fetch(`/visitor/${id}/checkout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(async response => {
            let data = null;

            try {
                data = await response.json();
            } catch (parseError) {
                throw new Error('Server returned an invalid checkout response.');
            }

            // Handle duplicate checkout (409) as success - already checked out, update UI
            if (response.status === 409 && data?.visitor) {
                updateVisitorRow(data.visitor);
                showCheckoutSuccessModal(data.message || 'Visitor already checked out.');
                loadTodaysVisitors();
                return data;
            }

            if (!response.ok) {
                throw new Error(data?.message || 'Unable to check out visitor.');
            }

            if (data.visitor) {
                updateVisitorRow(data.visitor);
            }

            showCheckoutSuccessModal(data.message || 'Visitor checked out successfully.');

            return data;
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Unable to check out visitor.');
            // Re-enable button on error so user can retry (unless already checked out)
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sign-out-alt"></i>';
                btn.style.opacity = '1';
                btn.style.pointerEvents = 'auto';
            }
        });
    }

    if (document.getElementById('checkoutSuccessModal')) {
        document.getElementById('checkoutSuccessModal').addEventListener('hidden.bs.modal', function() {
            loadTodaysVisitors();
        });
    }

    if (visitorSuccessModalEl) {
        visitorSuccessModalEl.addEventListener('hidden.bs.modal', function() {
            loadTodaysVisitors();
        });
    }

    function viewVisitor(id) {
        // Implement view modal if needed
        alert('View visitor details for ID: ' + id);
    }

    // Form submission
    document.getElementById('visitorForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('full_name', document.querySelector('input[name="full_name"]').value);
        formData.append('phone', document.querySelector('input[name="phone"]').value);
        formData.append('person_to_visit', document.querySelector('select[name="person_to_visit"]').value);
        formData.append('purpose', document.querySelector('select[name="purpose"]').value);
        formData.append('remarks', document.querySelector('textarea[name="remarks"]').value);
        const photoBase64 = document.getElementById('photoInput').value;
        if (photoBase64 && photoBase64.startsWith('data:image/')) {
            formData.append('photo_base64', photoBase64);
        }

        // Add photo as an actual file for backend validation/storage.
        const photoInput = document.getElementById('photoFile');
        if (photoInput.files.length > 0) {
            formData.append('photo', photoInput.files[0]);
        } else if (currentPhoto && currentPhoto.startsWith('data:image/')) {
            const capturedFile = dataUrlToFile(currentPhoto, `visitor_${Date.now()}.jpg`);
            formData.append('photo', capturedFile);
        }

        fetch('{{ route("visitor.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            let payload = null;
            try {
                payload = await response.json();
            } catch (parseError) {
                throw new Error('Server returned an invalid response.');
            }

            if (!response.ok) {
                const validationMessage = payload?.message
                    || Object.values(payload?.errors || {}).flat().join(' ')
                    || 'Error registering visitor.';
                throw new Error(validationMessage);
            }

            return payload;
        })
        .then(data => {
            showVisitorSuccess(data.message || 'Visitor registered successfully!');
            document.getElementById('visitorForm').reset();
            removePhoto();
            if (data.visitor) {
                prependVisitorRow(data.visitor);
            }
            loadTodaysVisitors();
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Error registering visitor. Check console for details.');
        });
    });

    // Load visitors on page load
    loadTodaysVisitors();
    document.addEventListener('DOMContentLoaded', loadTodaysVisitors);
    window.addEventListener('pageshow', loadTodaysVisitors);

    // Refresh visitors every 30 seconds
    setInterval(loadTodaysVisitors, 30000);
</script>
@endsection
