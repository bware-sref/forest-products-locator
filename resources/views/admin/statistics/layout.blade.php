@extends(backpack_view('blank'))

{{--
  Shared frame for the Statistics pages: heading, page tabs, chart colors, and the chart/map scripts.
  Pages fill @section('stats'), and may fill @section('stats-filter') (shown beside the heading).

  Chart colors are roles (see App\Statistics\Chart), defined here per theme. The categorical eight and the ordinal
  ramps were checked with the dataviz palette validator against these cards (#fbfcfd light, #2f454d dark):
  every hard check passes; some light hues sit under 3:1 against the card, so every chart has a data table.
--}}

@php
    $tabs = [
        'index' => ['Overview', 'statistics'],
        'state' => ['By State', 'statistics/state'],
        'submissions' => ['Public Submissions', 'statistics/submissions'],
        'activity' => ['Activity', 'statistics/activity'],
        'similar' => ['Similar Names', 'statistics/similar'],
    ];
    if ($access->canCompareStates()) {
        $tabs['compare'] = ['Compare States', 'statistics/compare'];
    }
@endphp

@push('after_styles')
@if (! empty($map))
    @basset('https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css')
@endif
<style>
    .stats-root {
        --stats-surface: #fbfcfd;
        --stats-text: #2b2a2e;
        --stats-muted: #5c6166;
        --stats-grid: #e1e0d9;
        --stats-axis: #c3c2b7;
        --series-1: #2a78d6;
        --series-2: #eb6834;
        --series-3: #1baf7a;
        --series-4: #eda100;
        --series-5: #e87ba4;
        --series-6: #008300;
        --series-7: #6250d6;
        --series-8: #e34948;
        --series-other: #898781;
        /* ordinal: more = darker on light */
        --ord-1: #86b6ef;
        --ord-2: #5598e7;
        --ord-3: #2a78d6;
        --ord-4: #1c5cab;
        --ord-5: #0d366b;
        --map-zero: #eceae4;
    }
    [data-bs-theme=dark] .stats-root {
        --stats-surface: #2f454d;
        --stats-text: #f0f1f2;
        --stats-muted: #d3d8d6;
        --stats-grid: #44585f;
        --stats-axis: #627479;
        --series-1: #3987e5;
        --series-2: #d95926;
        --series-3: #199e70;
        --series-4: #c98500;
        --series-5: #d55181;
        --series-6: #008300;
        --series-7: #9085e9;
        --series-8: #e66767;
        /* ordinal: more = lighter on dark */
        --ord-1: #2a78d6;
        --ord-2: #5598e7;
        --ord-3: #86b6ef;
        --ord-4: #b7d3f6;
        --ord-5: #e6f0fd;
        --map-zero: #3c535b;
    }
    .stats-root h2.card-title { font-size: 1rem; font-weight: 600; }
    .stats-root .stats-canvas { position: relative; width: 100%; }
    .stats-root .stats-tile .stats-value { font-size: 1.75rem; font-weight: 600; line-height: 1.2; }
    .stats-root .stats-tile .stats-label { color: var(--tblr-secondary-color); }
    .stats-root .stats-spark { height: 40px; margin-top: .5rem; }
    .stats-root details.stats-table > summary { cursor: pointer; color: var(--tblr-secondary-color); font-size: .8125rem; }
    .stats-root details.stats-table table { font-variant-numeric: tabular-nums; }
    .stats-root td.num, .stats-root th.num { text-align: end; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .stats-root .stats-swatch { display: inline-block; width: .875rem; height: .875rem; border-radius: 3px; vertical-align: -2px; margin-inline-end: .375rem; }
    .stats-root .stats-map { height: 440px; background: var(--stats-surface); border-radius: 4px; }
    .stats-root .stats-map .leaflet-tooltip { font-size: .8125rem; }
    .stats-root .stats-heat td.heat { text-align: end; font-variant-numeric: tabular-nums; }
    /* heat cells: one blue ramp, light to dark; text flips to white on the two darkest steps */
    .stats-root .heat-0 { background: transparent; }
    .stats-root .heat-1 { background: #cde2fb; color: #0b0b0b; }
    .stats-root .heat-2 { background: #9ec5f4; color: #0b0b0b; }
    .stats-root .heat-3 { background: #6da7ec; color: #0b0b0b; }
    .stats-root .heat-4 { background: #256abf; color: #fff; }
    .stats-root .heat-5 { background: #104281; color: #fff; }
    .stats-root .nav-tabs .nav-link.active { font-weight: 600; }
    @media print {
        .stats-root details.stats-table { display: block; }
        .stats-root details.stats-table > summary { display: none; }
    }
</style>
@endpush

@section('content')
<div class="stats-root">
    <section class="header-operation container-fluid animated fadeIn d-flex flex-wrap gap-2 mb-2 align-items-baseline justify-content-between d-print-none" bp-section="page-header">
        <div class="d-flex align-items-baseline">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">{{ $heading }}</h1>
            <p class="ms-2 mb-0 text-secondary" bp-section="page-subheading">Statistics</p>
        </div>
        @yield('stats-filter')
    </section>

    <nav class="container-fluid mb-3" aria-label="Statistics pages">
        <ul class="nav nav-tabs">
            @foreach ($tabs as $key => [$label, $path])
                <li class="nav-item">
                    <a class="nav-link {{ $current === $key ? 'active' : '' }}" href="{{ backpack_url($path) }}" @if ($current === $key) aria-current="page" @endif>{{ $label }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    <section class="content container-fluid animated fadeIn" bp-section="content">
        @yield('stats')
    </section>
</div>
@endsection

@push('after_scripts')
@basset('https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js')
@if (! empty($map))
    @basset('https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js')
    @basset('https://cdn.jsdelivr.net/npm/topojson-client@3.1.0/dist/topojson-client.min.js')
    @basset('https://cdn.jsdelivr.net/npm/us-atlas@3.0.1/counties-10m.json', false)
@endif
<script src="{{ asset('assets/js/admin/statistics.js') }}?v={{ @filemtime(public_path('assets/js/admin/statistics.js')) }}"></script>
@endpush
