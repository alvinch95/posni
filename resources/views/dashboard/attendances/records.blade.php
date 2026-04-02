@extends('dashboard.layouts.main')

@section('head')
<style>
    .timesheet-container {
        display: flex;
        width: 100%;
    }
    .timesheet-fixed {
        flex-shrink: 0;
    }
    .timesheet-scroll {
        overflow-x: auto;
        flex-grow: 1;
    }
    .timesheet-fixed table,
    .timesheet-scroll table {
        font-size: 11px;
        white-space: nowrap;
        border-collapse: collapse;
        width: 100%;
    }
    .timesheet-fixed table th,
    .timesheet-fixed table td,
    .timesheet-scroll table th,
    .timesheet-scroll table td {
        padding: 6px 8px;
        text-align: center;
        border: 1px solid #dee2e6;
    }
    .timesheet-fixed table th,
    .timesheet-scroll table th {
        background: #4a4036;
        color: #fff;
    }
    .timesheet-fixed table {
        border-right: 2px solid #aaa;
    }
    .timesheet-fixed .col-num { width: 30px; }
    .timesheet-fixed .col-name { width: 140px; text-align: left !important; }
    .timesheet-fixed .col-total { width: 60px; }
    .timesheet-scroll table th,
    .timesheet-scroll table td {
        min-width: 75px;
    }
    .timesheet-fixed table tbody td { background: #fff; }
    .timesheet-fixed table tfoot td { background: #f5f0eb; font-weight: 600; }
    .timesheet-scroll table tfoot td { background: #f5f0eb; font-weight: 600; }
    .col-sunday { background-color: #3a3330 !important; color: #d4cfc9 !important; }
    .timesheet-scroll table thead .col-sunday { background-color: #2c2420 !important; }
    .timesheet-scroll table tfoot .col-sunday { background-color: #3a3330 !important; color: #d4cfc9 !important; }
    .col-late { background-color: #f8d7da !important; color: #842029 !important; }
    .timesheet-scroll table tbody tr:hover td { background-color: #fff9e6; }
    .timesheet-scroll table tbody tr:hover td.col-sunday { background-color: #4a4540; }
    .timesheet-scroll table tbody tr:hover td.col-late { background-color: #f0c5ca; }
    .timesheet-fixed table tbody tr:hover td { background-color: #fff9e6; }
    .day-header-name { display: block; font-size: 9px; font-weight: normal; opacity: 0.8; }
    .row-hover td { background-color: #fff9e6 !important; }
    .row-hover td.col-sunday { background-color: #4a4540 !important; }
    .row-hover td.col-late { background-color: #f0c5ca !important; }
    .cell-has-data { cursor: pointer; }
    .cell-has-data:hover { outline: 2px solid #8c9c84; outline-offset: -2px; }
    .badge-late { background-color: #dc3545; color: #fff; font-size: 10px; }
</style>
@endsection

@section('container')
<div class="container-fluid mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Rangkuman Absensi</h4>
        <form method="GET" action="{{ route('attendance.records') }}" class="d-flex gap-2 align-items-center">
            <select name="month" class="form-select form-select-sm" style="width:130px;" onchange="this.form.submit()">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                    </option>
                @endfor
            </select>
            <select name="year" class="form-select form-select-sm" style="width:90px;" onchange="this.form.submit()">
                @for ($y = now()->year; $y >= now()->year - 2; $y--)
                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>

    <div class="card">
        <div class="card-body p-2">
            <div class="timesheet-container">
                {{-- Fixed left table --}}
                <div class="timesheet-fixed">
                    <table id="tableFixed">
                        <thead>
                            <tr>
                                <th class="col-num">#</th>
                                <th class="col-name">Nama Karyawan</th>
                                <th class="col-total">Hari</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summary as $index => $employee)
                                <tr>
                                    <td class="col-num">{{ $index + 1 }}</td>
                                    <td class="col-name">{{ $employee['name'] }}</td>
                                    <td class="col-total">
                                        <strong>{{ $employee['total_days'] }}</strong>
                                        @if ($employee['late_count'] > 0)
                                            <br><span class="badge badge-late">{{ $employee['late_count'] }} telat</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Tidak ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($summary->count() > 0)
                        <tfoot>
                            <tr>
                                <td class="col-num"></td>
                                <td class="col-name text-end"><strong>Total</strong></td>
                                <td class="col-total"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>

                {{-- Scrollable dates table --}}
                <div class="timesheet-scroll">
                    <table id="tableScroll">
                        <thead>
                            <tr>
                                @for ($d = 1; $d <= $daysInMonth; $d++)
                                    @php
                                        $date = \Carbon\Carbon::create($selectedYear, $selectedMonth, $d);
                                        $isSunday = $date->dayOfWeek === 0;
                                        $dayNames = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
                                        $dayName = $dayNames[$date->dayOfWeek];
                                    @endphp
                                    <th class="{{ $isSunday ? 'col-sunday' : '' }}">
                                        {{ $d }}
                                        <span class="day-header-name">{{ $dayName }}</span>
                                    </th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summary as $index => $employee)
                                <tr>
                                    @for ($d = 1; $d <= $daysInMonth; $d++)
                                        @php
                                            $dateKey = \Carbon\Carbon::create($selectedYear, $selectedMonth, $d)->format('Y-m-d');
                                            $date = \Carbon\Carbon::create($selectedYear, $selectedMonth, $d);
                                            $isSunday = $date->dayOfWeek === 0;
                                            $day = $employee['days'][$dateKey] ?? null;
                                            $isLate = false;
                                            if ($day && $day['in'] !== '-') {
                                                $isLate = $day['in'] > '08:05';
                                            }
                                        @endphp
                                        @if ($day)
                                            <td class="{{ $isSunday ? 'col-sunday' : '' }} {{ $isLate ? 'col-late' : '' }} cell-has-data"
                                                data-in-photo="{{ $day['in_photo'] ? asset('storage/' . $day['in_photo']) : '' }}"
                                                data-out-photo="{{ $day['out_photo'] ? asset('storage/' . $day['out_photo']) : '' }}"
                                                data-in-lat="{{ $day['in_lat'] }}" data-in-lng="{{ $day['in_lng'] }}"
                                                data-out-lat="{{ $day['out_lat'] }}" data-out-lng="{{ $day['out_lng'] }}"
                                                data-employee="{{ $employee['name'] }}"
                                                data-date="{{ $dateKey }}"
                                                data-in-time="{{ $day['in'] }}" data-out-time="{{ $day['out'] }}">
                                                {{ $day['in'] }}<br>{{ $day['out'] }}
                                            </td>
                                        @else
                                            <td class="{{ $isSunday ? 'col-sunday' : '' }}">-</td>
                                        @endif
                                    @endfor
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $daysInMonth }}" class="text-center text-muted py-3">Tidak ada data absensi untuk bulan ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($summary->count() > 0)
                        <tfoot>
                            <tr>
                                @for ($d = 1; $d <= $daysInMonth; $d++)
                                    @php
                                        $dateKey = \Carbon\Carbon::create($selectedYear, $selectedMonth, $d)->format('Y-m-d');
                                        $date = \Carbon\Carbon::create($selectedYear, $selectedMonth, $d);
                                        $isSunday = $date->dayOfWeek === 0;
                                        $count = $summary->filter(fn($e) => isset($e['days'][$dateKey]))->count();
                                    @endphp
                                    <td class="{{ $isSunday ? 'col-sunday' : '' }}">{{ $count > 0 ? $count : '-' }}</td>
                                @endfor
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="attendanceDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalTitle">Detail Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success"><i class="bi bi-box-arrow-in-right"></i> Check In</h6>
                        <p class="mb-1"><strong>Jam:</strong> <span id="detail-in-time"></span></p>
                        <p class="mb-1" id="detail-in-location-row"><strong>Lokasi:</strong> <a id="detail-in-location" href="#" target="_blank"><i class="bi bi-geo-alt-fill text-danger"></i> Lihat di Maps</a></p>
                        <div id="detail-in-photo-wrap" class="mt-2">
                            <img id="detail-in-photo" src="" class="img-fluid rounded" style="max-height:250px;" alt="Check-in Photo">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-danger"><i class="bi bi-box-arrow-right"></i> Check Out</h6>
                        <p class="mb-1"><strong>Jam:</strong> <span id="detail-out-time"></span></p>
                        <p class="mb-1" id="detail-out-location-row"><strong>Lokasi:</strong> <a id="detail-out-location" href="#" target="_blank"><i class="bi bi-geo-alt-fill text-danger"></i> Lihat di Maps</a></p>
                        <div id="detail-out-photo-wrap" class="mt-2">
                            <img id="detail-out-photo" src="" class="img-fluid rounded" style="max-height:250px;" alt="Check-out Photo">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sync row heights between fixed and scroll tables
    function syncRowHeights() {
        const fixedRows = document.querySelectorAll('#tableFixed thead tr, #tableFixed tbody tr, #tableFixed tfoot tr');
        const scrollRows = document.querySelectorAll('#tableScroll thead tr, #tableScroll tbody tr, #tableScroll tfoot tr');
        fixedRows.forEach(function(row, i) {
            if (scrollRows[i]) {
                const h = Math.max(row.offsetHeight, scrollRows[i].offsetHeight);
                row.style.height = h + 'px';
                scrollRows[i].style.height = h + 'px';
            }
        });
    }
    syncRowHeights();

    // Sync hover between tables
    const fixedBodyRows = document.querySelectorAll('#tableFixed tbody tr');
    const scrollBodyRows = document.querySelectorAll('#tableScroll tbody tr');
    fixedBodyRows.forEach(function(row, i) {
        row.addEventListener('mouseenter', function() { if (scrollBodyRows[i]) scrollBodyRows[i].classList.add('row-hover'); });
        row.addEventListener('mouseleave', function() { if (scrollBodyRows[i]) scrollBodyRows[i].classList.remove('row-hover'); });
    });
    scrollBodyRows.forEach(function(row, i) {
        row.addEventListener('mouseenter', function() { if (fixedBodyRows[i]) fixedBodyRows[i].classList.add('row-hover'); });
        row.addEventListener('mouseleave', function() { if (fixedBodyRows[i]) fixedBodyRows[i].classList.remove('row-hover'); });
    });

    // Cell click for detail modal
    document.querySelectorAll('.cell-has-data').forEach(function(cell) {
        cell.addEventListener('click', function() {
            const d = this.dataset;
            document.getElementById('detailModalTitle').textContent = d.employee + ' — ' + d.date;
            document.getElementById('detail-in-time').textContent = d.inTime || '-';
            document.getElementById('detail-out-time').textContent = d.outTime || '-';

            const inPhotoWrap = document.getElementById('detail-in-photo-wrap');
            if (d.inPhoto) { document.getElementById('detail-in-photo').src = d.inPhoto; inPhotoWrap.style.display = 'block'; }
            else { inPhotoWrap.style.display = 'none'; }

            const outPhotoWrap = document.getElementById('detail-out-photo-wrap');
            if (d.outPhoto) { document.getElementById('detail-out-photo').src = d.outPhoto; outPhotoWrap.style.display = 'block'; }
            else { outPhotoWrap.style.display = 'none'; }

            const inLocRow = document.getElementById('detail-in-location-row');
            if (d.inLat && d.inLat !== '') {
                document.getElementById('detail-in-location').href = 'https://www.google.com/maps/search/?api=1&query=' + d.inLat + ',' + d.inLng;
                inLocRow.style.display = 'block';
            } else { inLocRow.style.display = 'none'; }

            const outLocRow = document.getElementById('detail-out-location-row');
            if (d.outLat && d.outLat !== '') {
                document.getElementById('detail-out-location').href = 'https://www.google.com/maps/search/?api=1&query=' + d.outLat + ',' + d.outLng;
                outLocRow.style.display = 'block';
            } else { outLocRow.style.display = 'none'; }

            new bootstrap.Modal(document.getElementById('attendanceDetailModal')).show();
        });
    });
});
</script>
@endsection
