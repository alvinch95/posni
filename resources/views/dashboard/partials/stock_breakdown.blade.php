{{--
  A hamper's stock with the items behind it (see App\Services\HamperStockBreakdown).

    @include('dashboard.partials.stock_breakdown', ['breakdown' => $breakdown])

  Params:
    breakdown  HamperStockBreakdown::for()/forMany() result for one hamper.
    compact    bool, default true: at most 3 lines, then "+N lainnya". false lists every line.
    showStock  bool, default true: the stock number. Pass false when the page already shows it as a badge.
    showList   bool, default true: the "Kurang:" / "Hampir habis:" lines. Pass false for the number alone.

  The number is red when the hamper is out (0), amber when it is low, plain otherwise. Out lists the
  items that are at zero or below ("Kurang:"); low lists the items that hold the number down ("Hampir
  habis:"); ok lists nothing. Everything is escaped: names come from the items table.
--}}
@php
    $compact = $compact ?? true;
    $showStock = $showStock ?? true;
    $showList = $showList ?? true;

    $status = $breakdown['status'];
    $isOut = $status === 'out';
    $isLow = $status === 'low';

    $lines = $isOut ? $breakdown['blocking'] : ($isLow ? $breakdown['limiting'] : []);
    $shown = $compact ? array_slice($lines, 0, 3) : $lines;
    $more = count($lines) - count($shown);
@endphp
<div class="stock-breakdown">
    @if ($showStock)
        <span class="stock-breakdown-number{{ $isOut ? ' text-danger fw-bold' : ($isLow ? ' fw-bold' : '') }}"@if ($isLow) style="color: #b45309;"@endif>{{ $breakdown['stock'] }}</span>
    @endif
    @if ($showList && count($shown) > 0)
        <div class="small text-break{{ $showStock ? ' mt-1' : '' }}" style="min-width: 0;">
            <strong class="{{ $isOut ? 'text-danger' : '' }}"@if ($isLow) style="color: #b45309;"@endif>{{ $isOut ? 'Kurang:' : 'Hampir habis:' }}</strong>
            <ul class="list-unstyled mb-0{{ $isLow ? ' text-muted' : '' }}">
                @foreach ($shown as $line)
                    <li style="padding-left: 1em; text-indent: -1em;">
                        &bull;
                        @if ($line['deleted'])
                            <span class="text-danger">{{ $line['name'] }}</span> &mdash; butuh {{ $line['needed'] }}
                        @else
                            {{ $line['name'] }} &mdash; stok {{ $line['stock'] }} / butuh {{ $line['needed'] }}
                            @if ($line['reserved'] > 0)
                                ({{ $line['reserved'] }} di keranjang)
                            @endif
                        @endif
                    </li>
                @endforeach
                @if ($more > 0)
                    <li class="text-muted">+{{ $more }} lainnya</li>
                @endif
            </ul>
        </div>
    @endif
</div>
