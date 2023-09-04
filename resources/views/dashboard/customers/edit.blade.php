@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Customer</h1>
</div>

<div class="col-lg-8">
    <form method="post" action="/dashboard/customers/{{ $customer->id }}" class="mb-5">
        @method('patch')
        @csrf
        <div class="mb-3">
          <label for="name" class="form-label">Name</label>
          <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" autofocus value="{{ old('name', $customer->name) }}">
          @error('name')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3">
          <label for="fee" class="form-label">Fee</label>
          <input type="number" class="form-control @error('fee') is-invalid @enderror" id="fee" name="fee" autofocus step="0.01" value="{{ old('fee', $customer->fee) }}">
          @error('fee')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3">
          <label for="notes" class="form-label">Notes</label>
          <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="6">{{ old('notes', $customer->notes) }}</textarea>
          @error('notes')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <button type="submit" class="btn btn-primary">Update Customer</button>
    </form>
</div>

@endsection