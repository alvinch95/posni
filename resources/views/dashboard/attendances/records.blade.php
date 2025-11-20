{{-- resources/views/attendance/records.blade.php --}}

@extends('dashboard.layouts.main')

@section('container')
<div class="container mt-5">
    <h2>Employee Attendance History (Last 1 Month)</h2>
    
    @foreach ($attendance as $date => $records)
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Date: {{ $date }}
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Action</th>
                            <th>Time</th>
                            <th>Location (Lat/Lon)</th>
                            <th>Photo Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($records as $record)
                        <tr>
                            <td>{{ $record->user->name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $record->action_type == 'in' ? 'bg-success' : 'bg-danger' }}">
                                    {{ strtoupper($record->action_type) }}
                                </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($record->action_time)->format('H:i:s') }}</td>
                            <td>
                                @php
                                    $mapUrl = "https://www.google.com/maps/search/?api=1&query={$record->latitude},{$record->longitude}";
                                @endphp
                                <a href="{{ $mapUrl }}" target="_blank" title="View location on map">
                                    <small>{{ $record->latitude }}, {{ $record->longitude }}</small>
                                    <i class="bi bi-geo-alt-fill text-danger"></i>
                                </a>
                            </td>
                            <td>
                                <a href="#" 
                                data-bs-toggle="modal" 
                                data-bs-target="#imageModal" 
                                data-image-url="{{ asset('storage/' . $record->photo_path) }}" 
                                class="thumbnail-link">
                                    <img src="{{ asset('storage/' . $record->photo_path) }}" 
                                        alt="Photo" 
                                        style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Employee Check-In Photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        {{-- The image will be loaded here by JavaScript --}}
        <img id="modalImage" src="" class="img-fluid" alt="Check-in Photo">
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const imageModal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');

    // This listener fires just before the Bootstrap modal shows up
    imageModal.addEventListener('show.bs.modal', function (event) {
        // Get the button/link that triggered the modal
        const link = event.relatedTarget; 
        
        // Extract the URL stored in the custom data attribute
        const imageUrl = link.getAttribute('data-image-url');
        
        // Set the src attribute of the image inside the modal
        modalImage.src = imageUrl;
    });
});
</script>
@endsection