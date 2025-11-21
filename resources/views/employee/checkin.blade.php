
@extends('dashboard.layouts.main')
{{-- Assuming a layout exists --}}

@section('container')
<div class="container mt-5">
    <h2>Absensi Karyawan</h2>
    <div id="status-message" class="alert d-none" role="alert"></div>

    <form id="attendance-form" enctype="multipart/form-data">
        @csrf {{-- Laravel CSRF token for security --}}
        
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-camera"></i> Foto
            </div>
            <div class="card-body text-center">
                <video id="webcam-stream" class="img-fluid border" autoplay style="max-width: 100%; height: auto;"></video>
                <canvas id="photo-canvas" style="display:none; width: 100%; height: auto;"></canvas>
                <div class="mt-3">
                    <button type="button" class="btn btn-warning" id="start-camera-btn">Buka Kamera</button>
                    <button type="button" class="btn btn-secondary d-none" id="switch-camera-btn">Switch Camera</button>
                    <button type="button" class="btn btn-primary d-none" id="capture-photo-btn">Ambil Foto</button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-map-marker-alt"></i> Status Lokasi
            </div>
            <div class="card-body">
                <p id="location-status" class="text-info">Sedang mencatat lokasi...</p>
                <input type="hidden" name="latitude" id="latitude-input">
                <input type="hidden" name="longitude" id="longitude-input">
            </div>
        </div>

        <button type="submit" class="btn btn-success btn-lg btn-block" id="submit-btn" disabled>
            <span id="action-text">...</span> Absen
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const form = document.getElementById('attendance-form');
        const webcamStream = document.getElementById('webcam-stream');
        const canvas = document.getElementById('photo-canvas');
        const startCameraBtn = document.getElementById('start-camera-btn');
        const capturePhotoBtn = document.getElementById('capture-photo-btn');
        const submitBtn = document.getElementById('submit-btn');
        const locationStatus = document.getElementById('location-status');
        const actionText = document.getElementById('action-text');
        let stream = null; // To store the video stream object
        let photoBlob = null; // To store the captured photo file
        const switchCameraBtn = document.getElementById('switch-camera-btn');
        let useFrontCamera = true; // NEW STATE: Start with the front camera (user-facing)

        // --- 1. Get Initial Status and Set Button Text (Simulated for now) ---
        // In a real app, you would make a small API call to see the employee's last action 
        // to determine if the next action is 'IN' or 'OUT'.
        // For now, we default to 'IN'
        let nextAction = 'IN'; 
        async function loadInitialStatus() {
            try {
                const response = await fetch('{{ route('attendance.status') }}'); 
                const data = await response.json();
                
                nextAction = data.next_action; // e.g., 'in' or 'out'
                
                // Update the button text based on the determined action
                actionText.textContent = nextAction === 'in' ? 'Check-In' : 'Check-Out';
                
            } catch (error) {
                console.error('Failed to load initial status:', error);
                actionText.textContent = 'Error';
            }
        }
        loadInitialStatus();

        // --- 2. Geolocation Function ---
        function getLocation() {
            if (!navigator.geolocation) {
                locationStatus.textContent = 'Geolocation is not supported by your browser.';
                return false;
            }

            locationStatus.textContent = 'Fetching location...';
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    // Success: Populate hidden fields
                    document.getElementById('latitude-input').value = position.coords.latitude;
                    document.getElementById('longitude-input').value = position.coords.longitude;
                    locationStatus.innerHTML = `Sukses ambil lokasi! <br>Lat: ${position.coords.latitude.toFixed(4)}, Lon: ${position.coords.longitude.toFixed(4)}`;
                    
                    // If photo is also ready, enable submit
                    if (photoBlob) {
                        submitBtn.disabled = false;
                    }
                },
                (error) => {
                    // Failure
                    locationStatus.textContent = `Error getting location: ${error.message}. Cannot proceed.`;
                    submitBtn.disabled = true;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        }
        
        function stopCameraStream() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                webcamStream.srcObject = null;
                stream = null;
            }
        }

        async function startCameraAndLocation() {
            // 1. Stop any existing stream
            stopCameraStream(); 
            canvas.style.display = 'none';
            webcamStream.style.display = 'block';

            try {
                const videoConstraints = {
                    video: {
                        // Use 'user' for front camera, 'environment' for back camera
                        facingMode: useFrontCamera ? 'user' : 'environment' 
                    }
                };

                stream = await navigator.mediaDevices.getUserMedia(videoConstraints);
                webcamStream.srcObject = stream;
                
                startCameraBtn.classList.add('d-none');
                capturePhotoBtn.classList.remove('d-none');
                switchCameraBtn.classList.remove('d-none'); // Show the switch button

                getLocation(); 
            } catch (err) {
                alert('Error accessing camera: ' + err.name + '. Check permissions or switch setting.');
                console.error(err);
            }
        }

        // --- 3. Camera Controls ---
        startCameraBtn.onclick = async () => {
            useFrontCamera = !useFrontCamera; // Toggle camera facing mode
            switchCameraBtn.textContent = useFrontCamera ? 'Kamera Belakang' : 'Kamera Depan';
            await startCameraAndLocation();
            canvas.style.display = 'none';
            webcamStream.style.display = 'block';
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                webcamStream.srcObject = stream;
                
                startCameraBtn.classList.add('d-none');
                capturePhotoBtn.classList.remove('d-none');
                
                getLocation();
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Camera Error',
                    text: 'Tidak bisa akses kamera. Mohon untuk diberikan permission.'
                });
                console.error(err);
            }
        };
        
        capturePhotoBtn.onclick = () => {
            if (capturePhotoBtn.textContent === 'Retake Photo') {
                // --- ACTION: RETAKE (Re-open the stream) ---
                canvas.style.display = 'none';
                webcamStream.style.display = 'block';
                
                // Reset button state to original capture state
                capturePhotoBtn.textContent = 'Ambil Foto'; 
                capturePhotoBtn.classList.remove('btn-danger');
                capturePhotoBtn.classList.add('btn-primary'); 
                
                // Restart the camera stream
                startCameraBtn.onclick(); 
                
            } else {
                // --- ACTION: CAPTURE (Stop stream and show preview) ---
                const context = canvas.getContext('2d');
                canvas.width = webcamStream.videoWidth;
                canvas.height = webcamStream.videoHeight;
                context.drawImage(webcamStream, 0, 0, canvas.width, canvas.height);
                
                canvas.toBlob((blob) => {
                    photoBlob = blob;
                    webcamStream.style.display = 'none'; // Hide the live stream
                    canvas.style.display = 'block';      // Show the captured image preview
                    stopCameraStream(); 
                    switchCameraBtn.classList.add('d-none');

                    capturePhotoBtn.textContent = 'Retake Photo';
                    capturePhotoBtn.classList.remove('btn-primary');
                    capturePhotoBtn.classList.add('btn-danger');

                    if (document.getElementById('latitude-input').value) {
                        submitBtn.disabled = false;
                    }
                }, 'image/jpeg');
            }
        };

        // --- 4. Submission Handler ---
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!photoBlob || !document.getElementById('latitude-input').value) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "Error: Photo and Location are mandatory"
                })
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';

            // 4a. Create FormData object (required for file upload)
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]').value);
            formData.append('latitude', document.getElementById('latitude-input').value);
            formData.append('longitude', document.getElementById('longitude-input').value);
            
            // Append the photo blob as a file named 'photo'
            formData.append('photo', photoBlob, 'checkin_proof.jpg'); 

            try {
                // 4b. Send the request
                const response = await fetch('{{ route('attendance.submit') }}', {
                    method: 'POST',
                    body: formData, // FormData handles the 'multipart/form-data' header
                    // Note: CSRF token is included in FormData. No need for X-CSRF-TOKEN header here.
                    // Authorization token (if using Sanctum) would be added here if needed
                });
                console.log('Response : ',response);

                const data = await response.json();
                const statusMessage = document.getElementById('status-message');
                
                if (response.ok) {
                    // Stop camera stream on success
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                    }
                    
                    submitBtn.disabled = true;
                    submitBtn.textContent = nextAction === 'in' ? 'Checked In' : 'Checked Out';
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message
                    });

                } else {
                    submitBtn.disabled = false;
                    submitBtn.textContent = actionText.textContent + ' Attendance';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || "An error occurred during submission."
                    });
                }
            } catch (error) {
                console.error('Network Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'A network error occurred. Please check your connection and try again.'
                });
                submitBtn.disabled = false;
                submitBtn.textContent = actionText.textContent + ' Attendance';
            }
        });
        
        // --- 5. Clean up stream when navigating away ---
        window.onbeforeunload = function() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
        };
    });
</script>
@endpush