@extends('dashboard.layouts.main')


@section('container')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Welcome back, {{ auth()->user()->name }}</h1>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header" style="background-color: #008080; color: white;">
                Monthly Transactions
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <form action="{{ route('dashboard.index') }}" method="GET" class="d-flex align-items-center">
                        @csrf
                        <div>
                            <select class="form-select" id="year" name="year">
                                <option value="" disabled selected hidden>Select year</option>
                                @for ($year = 2023; $year <= $currentYear; $year++)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary ms-2">Apply</button>
                    </form>
                </div>
                {!! $chart->container() !!}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header" style="background-color: #C0C0C0; color: white;">
                Chart 2
            </div>
            <div class="card-body">
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header" style="background-color: #C8A2C8; color: white;">
                Chart 3
            </div>
            <div class="card-body">
            </div>
        </div>
    </div>

</div>

<script src="{{ $chart->cdn() }}"></script>

{{ $chart->script() }}
@endsection