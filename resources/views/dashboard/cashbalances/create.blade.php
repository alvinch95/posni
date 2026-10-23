@extends('dashboard.layouts.main')

@section('head')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Cash Balance</h1>
</div>

<div class="col-lg-8">
    {{-- enctype supaya formnya bisa submit gambar --}}
    <form method="post" action="/dashboard/cashbalances" class="mb-5" id="cashBalanceForm"> 
        @csrf
        <div class="mb-3 @desktop w-25 @elsedesktop w-100 @enddesktop">
          <label for="transaction_date" class="form-label">Tanggal Transaksi</label>
          <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" id="transaction_date" name="transaction_date" autofocus value="{{ \Carbon\Carbon::now()->format('Y-m-d'); }}" required>
          @error('transaction_date')
            <div class="invalid-feedback">
              {{ $message }}
            </div>
          @enderror
        </div>
        <div class="mb-3">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="cashType" id="cashType1" value="CashIn" {{ old('cashType', 'CashIn') === 'CashIn' ? 'checked' : '' }}>
            <label class="form-check-label" for="cashType1">
              Cash In
            </label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="cashType" id="cashType2" value="CashOut" {{ old('cashType') === 'CashOut' ? 'checked' : '' }}>
            <label class="form-check-label" for="cashType2">
              Cash Out
            </label>
          </div>
        </div>
        <div class="mb-3 w-75 {{ old('cashType') === 'CashOut' ? '' : 'd-none' }}" id="categoryField">
          <label for="category" class="form-label">Kategori Biaya</label>
          <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" {{ old('cashType') === 'CashOut' ? '' : 'disabled' }}>
            <option value="">-- Pilih kategori --</option>
            @foreach ($categories as $slug => $label)
              <option value="{{ $slug }}" {{ old('category') === $slug ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('category')
            <div class="invalid-feedback">
              Pilih kategori untuk Cash Out.
            </div>
          @enderror
        </div>
        <div class="mb-3 w-50">
          <label for="amount" class="form-label">Amount</label>
          <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" step="1" onkeypress="return event.charCode >= 48 && event.charCode <= 57" value="{{ old('amount') }}" required>
        </div>
        <div class="mb-3 w-75">
          <label for="remark" class="form-label">Remark</label>
          <input type="text" name="remark" id="remark" class="form-control" value="{{ old('remark') }}">
        </div>
        <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
    </form>
</div>

<script>
  $(document).ready(function() {
      // Category only applies to Cash Out (operating costs on the dashboard)
      function toggleCategory() {
        var isCashOut = $('input[name="cashType"]:checked').val() === 'CashOut';
        $('#categoryField').toggleClass('d-none', !isCashOut);
        $('#category').prop('disabled', !isCashOut);
      }
      $('input[name="cashType"]').on('change', toggleCategory);
      toggleCategory();

      $('#submitBtn').on('click', function (e) {
        e.preventDefault();
        var form = $('#cashBalanceForm');
        if ($('input[name="cashType"]:checked').val() === 'CashOut' && !$('#category').val()) {
            Swal.fire({ icon: 'warning', title: 'Kategori belum dipilih', text: 'Pilih kategori biaya untuk Cash Out.' });
            return;
        }
        Swal.fire({
            title: 'Are you sure ?',
            text: "This will update your cash",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, confirm!'
        }).then((result) => {
            if (result.value) {
                form.submit();
            }
        });
      });
  });
</script>

@endsection