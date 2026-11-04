@extends('admin.statistics.layout')

@section('stats-filter')
    @if ($states->count() > 1)
        <x-stats.state-filter :states="$states" :selected="$state" :action="backpack_url('statistics/state')" />
    @endif
@endsection

@section('stats')
<x-stats.kpis :kpis="$kpis" />

<div class="row">
    <div class="col-12 col-xl-6">
        <x-stats.chart :chart="$completeness">
            <p class="small mt-2 mb-0"><a href="{{ $millListUrl }}">Open {{ $state->name }}’s mills</a></p>
        </x-stats.chart>
    </div>
    <div class="col-12 col-xl-6">
        <x-stats.chart :chart="$completenessOverTime" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$dataAge" /></div>
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$millsOverTime" /></div>
</div>

{{-- county map --}}
<section class="card mb-3" aria-labelledby="map-title">
    <div class="card-body">
        <h2 class="card-title mb-1" id="map-title">Mills by county</h2>
        <p class="text-secondary small mb-3">Counties shaded by how many published mills they have. Dots are mills with a map location.</p>
        @if ($map)
            <div class="d-flex flex-wrap align-items-center gap-3 mb-2 small">
                <span><span class="stats-swatch" style="background: var(--map-zero)"></span>No mills</span>
                @foreach ($map['bins'] as $i => [$low, $high])
                    <span><span class="stats-swatch" style="background: var(--ord-{{ $i + 2 }})"></span>{{ $low === $high ? number_format($low) : number_format($low).'–'.number_format($high) }} mills</span>
                @endforeach
                <label class="form-check form-check-inline mb-0 ms-auto">
                    <input class="form-check-input" type="checkbox" checked data-stats-map-dots>
                    <span class="form-check-label">Show mills</span>
                </label>
            </div>
            {{-- the boundaries' path only: Basset's URL carries APP_URL's host, which may not be the page's origin --}}
            <div class="stats-map" role="img" aria-label="Map of {{ $state->name }} counties shaded by number of mills. The numbers are in the table below the map."
                data-stats-map="{{ json_encode($map + ['topoUrl' => parse_url(\Backpack\Basset\Facades\Basset::getUrl('https://cdn.jsdelivr.net/npm/us-atlas@3.0.1/counties-10m.json'), PHP_URL_PATH)]) }}"></div>
            <p class="small text-secondary mt-2 mb-0">County boundaries: U.S. Census Bureau, via us-atlas.</p>
        @else
            <p class="text-secondary mb-0">This state’s counties have no FIPS codes, so they can’t be mapped.</p>
        @endif
        <details class="stats-table mt-2">
            <summary>Show the numbers</summary>
            <div class="table-responsive mt-2" style="max-height: 24rem">
                <table class="table table-sm table-striped mb-0">
                    <caption class="visually-hidden">Mills per county in {{ $state->name }}</caption>
                    <thead><tr><th scope="col">County</th><th scope="col" class="num">Mills</th></tr></thead>
                    <tbody>
                        @foreach ($counties as $county)
                            <tr><th scope="row" class="fw-normal">{{ $county['name'] }}</th><td class="num">{{ number_format($county['mills']) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>
</section>

<div class="row">
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$types" /></div>
    <div class="col-12 col-xl-6">
        <x-stats.chart :chart="$typesOverTime" />
        <x-stats.chart :chart="$species" />
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$sizes" /></div>
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$added" /></div>
</div>

<div class="row">
    <div class="col-12 col-xl-6">
        <x-stats.chart :chart="$pendingAges">
            <p class="small mt-2 mb-0"><a href="{{ backpack_url('statistics/submissions') }}?state={{ $state->abbreviation }}">More about {{ $state->name }}’s public submissions</a></p>
        </x-stats.chart>
    </div>
    <div class="col-12 col-xl-6">
        <x-stats.chart :chart="$activityBySource">
            <p class="small mt-2 mb-0"><a href="{{ backpack_url('statistics/activity') }}?state={{ $state->abbreviation }}">More about changes to {{ $state->name }}’s mills</a></p>
        </x-stats.chart>
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-4">
        <section class="card mb-3" aria-labelledby="attention-title">
            <div class="card-body">
                <h2 class="card-title mb-1" id="attention-title">Mills needing attention</h2>
                <p class="text-secondary small mb-3">Mills flagged during import or geocoding, and mills missing from the map or county searches.</p>
                <table class="table table-sm mb-0">
                    <caption class="visually-hidden">Mills needing attention</caption>
                    <tbody>
                        @foreach ($attention as $label => $count)
                            <tr><th scope="row" class="fw-normal">{{ $label }}</th><td class="num">{{ number_format($count) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-4">
        <section class="card mb-3" aria-labelledby="stale-title">
            <div class="card-body">
                <h2 class="card-title mb-1" id="stale-title">Longest since an update</h2>
                <p class="text-secondary small mb-3">The ten mills that have gone longest without any change.</p>
                <table class="table table-sm mb-0">
                    <caption class="visually-hidden">Mills that have gone longest without an update</caption>
                    <thead><tr><th scope="col">Mill</th><th scope="col" class="num">Last updated</th></tr></thead>
                    <tbody>
                        @forelse ($leastRecent as $mill)
                            <tr>
                                <th scope="row" class="fw-normal">
                                    @if ($canEditMills)
                                        <a href="{{ backpack_url('mill/'.$mill->id.'/edit') }}">{{ $mill->mill_name }}</a>
                                    @else
                                        {{ $mill->mill_name }}
                                    @endif
                                    @if ($mill->physical_city)<span class="text-secondary">, {{ $mill->physical_city }}</span>@endif
                                </th>
                                <td class="num">{{ $mill->updated_at?->format('M j, Y') ?? '–' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-secondary">No mills yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-4">
        <section class="card mb-3" aria-labelledby="sections-title">
            <div class="card-body">
                <h2 class="card-title mb-1" id="sections-title">State page sections</h2>
                <p class="text-secondary small mb-3">
                    Each section of {{ $state->name }}’s public page and when it last changed.
                    @if ($canSeeStateHub)
                        <a href="{{ route('admin.page.state-hub.show', ['state' => $state]) }}">Edit the state page</a>
                    @endif
                </p>
                <table class="table table-sm mb-0">
                    <caption class="visually-hidden">State page sections and when they last changed</caption>
                    <thead><tr><th scope="col">Section</th><th scope="col" class="num">Entries</th><th scope="col" class="num">Last changed</th></tr></thead>
                    <tbody>
                        @foreach ($sections as $section)
                            <tr>
                                <th scope="row" class="fw-normal">{{ $section['title'] }}</th>
                                <td class="num">{{ $section['records'] ?: 'None yet' }}</td>
                                <td class="num">{{ $section['updated']?->format('M j, Y') ?? '–' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
