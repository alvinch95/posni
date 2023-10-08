@extends('dashboard.layouts.main')

@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Hampers</h1>
</div>

@if (session()->has('success'))
  <div class="alert alert-success col-lg-8" role="alert">
  {{ session('success') }}
  </div>
@endif

<div class="row mb-3">
  <div class="col-md-6">
      <form action="/dashboard/hampers">
          <div class="input-group mb-3">
              <input type="text" class="form-control" placeholder="Search hampers.." name="search" value="{{ request('search') }}">
              <button class="btn text-white" style="background-color: #966F33" type="submit">Search</button>
            </div>
      </form>

      <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'asc']) }}" class="btn btn-dark">Sort by Name (ASC)</a>
      <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'order' => 'desc']) }}" class="btn btn-outline-dark">Sort by Name (DESC)</a>

  </div>
</div>

<div class="table-responsive col-lg-8">
    <a href="/dashboard/hampers/create" class="btn btn-primary mb-3">Add new hampers</a>
    @if ($hampers->count())
    <div class="d-flex">
        <button class="btn btn-primary toggle-button" data-toggle="column-visibility" data-column="col-name"><span class="eye-off" data-feather="eye-off"></span><span class="eye-on" data-feather="eye" display="none"></span> Nama</button>
        <button class="btn btn-primary toggle-button" data-toggle="column-visibility" data-column="col-seri"><span class="eye-off" data-feather="eye-off"></span><span class="eye-on" data-feather="eye" display="none"></span> Seri</button>
        <button class="btn btn-primary toggle-button" data-toggle="column-visibility" data-column="col-modal-price"><span class="eye-off" data-feather="eye-off"></span><span class="eye-on" data-feather="eye" display="none"></span> Harga Modal</button>
        <button class="btn btn-primary toggle-button" data-toggle="column-visibility" data-column="col-selling-price"><span class="eye-off" data-feather="eye-off"></span><span class="eye-on" data-feather="eye" display="none"></span> Harga Jual</button>
        <button class="btn btn-primary toggle-button" data-toggle="column-visibility" data-column="col-revenue"><span class="eye-off" data-feather="eye-off"></span><span class="eye-on" data-feather="eye" display="none"></span> Cuan %</button>
        <button class="btn btn-primary toggle-button" data-toggle="column-visibility" data-column="col-stock"><span class="eye-off" data-feather="eye-off"></span><span class="eye-on" data-feather="eye" display="none"></span> Stok</button>
        <button class="btn btn-primary toggle-button" data-toggle="column-visibility" data-column="col-image"><span class="eye-off" data-feather="eye-off"></span><span class="eye-on" data-feather="eye" display="none"></span> Image</button>
    </div>
      <table class="table table-striped table-sm">
        <caption>
          <div class="d-flex float-end">
            <label for="page-size-select" class="mx-2">Page Size:</label>
            <select id="page-size-select" class="form-select page-size-select">
                <option value="10" {{ $pageSize==10?"selected":"" }}>10</option>
                <option value="25" {{ $pageSize==25?"selected":"" }}>25</option>
                <option value="50" {{ $pageSize==50?"selected":"" }}>50</option>
            </select>
          </div>
          Showing {{ $pageSize<=$totalData?$hampers->count():$totalData }} of {{ $totalData }} results
        </caption>
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col" class="col-name">Nama </th>
            <th scope="col" class="col-seri">Seri</th>
            <th scope="col" class="col-modal-price">Harga Modal</th>
            <th scope="col" class="col-selling-price">Harga Jual</th>
            <th scope="col" class="col-revenue">Cuan %</th>
            <th scope="col" class="col-stock">Stok</th>
            <th scope="col" class="col-image">Image</th>
            <th scope="col">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($hampers as $hamper)
              <tr>
                  <td>{{ $loop->iteration }}</td>
                  <td class="col-name">{{ $hamper->name }}</td>
                  <td class="col-seri">{{ $hamper->serie->name }}</td>
                  <td class="col-modal-price">{{ "Rp. ".number_format($hamper->capital_price, 0, ',', '.') }}</td>
                  <td class="col-selling-price">{{ "Rp. ".number_format($hamper->selling_price, 0, ',', '.') }}</td>
                  <td class="col-revenue">{{ $hamper->revenue_percentage }}%</td>
                  <td class="col-stock">{{ $hamper->getStock() }}</td>
                  @if ($hamper->image)
                  <td class="col-image"><img src="{{ asset('storage/' . $hamper->image) }}" alt="{{ $hamper->name }}" class="img-fluid" style="max-width: 50px; max-height: 50px;"></td>
                  @else
                  <td class="col-image"><img src="{{ asset('storage/hampers-images/no-image-found.jpg') }}" alt="{{ $hamper->name }}" class="img-fluid" style="max-width: 50px; max-height: 50px;"></td>
                  @endif
                  <td>
                      <a href="/dashboard/hampers/{{ $hamper->id }}" class="badge bg-primary"><span data-feather="eye"></span></a>
                      <a href="/dashboard/hampers/{{ $hamper->id }}/edit" class="badge bg-warning"><span data-feather="edit"></span></a>
                      <form action="/dashboard/hampers/{{ $hamper->id }}" method="post" class="d-inline">
                        @method('delete')
                        @csrf
                        <button class="badge bg-danger border-0 hapus"><span data-feather="x-circle"></span></button>
                      </form>
                      <a href="/dashboard/hampers/catalog/{{ $hamper->id }}" class="badge bg-secondary"><span data-feather="file"></span></a>
                  </td>
              </tr>
          @endforeach
        </tbody>
      </table>
      <div class="d-flex justify-content-center">
        {{ $hampers->links() }}
      </div>
    @else
      <p class="text-center fs-4">No Hampers Found.</p>
    @endif
  </div>
  <style>
    /* Style the page size select */
      .page-size-select {
          padding: 4px 8px; /* Adjust padding as needed */
          font-size: 12px; /* Adjust font size as needed */
          width: 70px; /* Adjust width as needed */
      }

      .toggle-button {
          background-color: teal; /* Change to teal color */
          color: #fff;
          border: none;
          border-radius: 4px;
          padding: 3px 8px; /* Smaller padding for a smaller button */
          margin-right: 10px;
          cursor: pointer;
          font-size: 14px; /* Smaller font size */
          transition: background-color 0.3s; /* Smooth transition for hover effect */
      }

      .toggle-button:hover {
          background-color: #38B6A4; /* Change to a highlight color when hovered */
      }
  </style>

  <script>
    $(document).ready(function(){

      function toggleColumnVisibility(columnClass, isVisible) {
        var $button = $('[data-column="' + columnClass + '"]');
        if (isVisible) {
            // $('.' + columnClass).show();
            $('.' + columnClass).fadeIn();
            $button.find('.eye-off').css('display', 'inline-block');
            $button.find('.eye-on').css('display', 'none');
        } else {
            // $('.' + columnClass).hide();
            $('.' + columnClass).fadeOut();
            $button.find('.eye-off').css('display', 'none');
            $button.find('.eye-on').css('display', 'inline-block');
        }
      }

      // Handle button clicks to toggle column visibility
      $('[data-toggle="column-visibility"]').on('click', function () {
          var columnClass = $(this).data('column');
          var isVisible = $('.' + columnClass).is(':visible');
          toggleColumnVisibility(columnClass, !isVisible);
      });

      $('#page-size-select').on('change', function() {
          console.log("test");
          const selectedPageSize = $(this).val();
          const currentUrl = window.location.href;

          // Replace the "page_size" query parameter with the selected page size
          const updatedUrl = updateQueryStringParameter(currentUrl, 'page_size', selectedPageSize);

          // Redirect to the updated URL
          window.location.href = updatedUrl;
      });

      // Function to update query parameters in URL
      function updateQueryStringParameter(uri, key, value) {
          const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
          const separator = uri.indexOf('?') !== -1 ? "&" : "?";
          if (uri.match(re)) {
              return uri.replace(re, '$1' + key + "=" + value + '$2');
          }
          return uri + separator + key + "=" + value;
      }

      $('.hapus').click(function(e) {
        e.preventDefault();
        var form = $(this).parents('form');
        Swal.fire({
            title: 'Are you sure ?',
            // text: "You won't be able to revert this!",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.value) {
                form.submit();
            }
        });
      });
    }); 
  </script>
@endsection