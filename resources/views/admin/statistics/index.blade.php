@extends('admin.statistics.layout')

@section('stats')
<x-stats.kpis :kpis="$kpis" />

<div class="row">
    <div class="col-12 col-xl-5">
        <x-stats.chart :chart="$millsByState">
            @if (! $access->isLockedToState())
                <p class="small text-secondary mt-2 mb-0">Select a bar to open that state’s statistics.</p>
            @endif
        </x-stats.chart>
    </div>
    <div class="col-12 col-xl-7">
        <x-stats.chart :chart="$totalOverTime" />
        <x-stats.chart :chart="$added" />
    </div>
</div>

{{-- Mills by state over time: one small chart per state, since a line per state on one chart couldn't be told apart --}}
<section class="card mb-3" aria-labelledby="state-trends-title">
    <div class="card-body">
        <h2 class="card-title mb-1" id="state-trends-title">Mills by state over time</h2>
        <p class="text-secondary small mb-3">
            Each state’s published mills over the last 90 days, with the change since 30 days ago.
            @if ($historyStarts && $historyStarts->gt(now()->subDays(30)))
                History started {{ $historyStarts->format('M j, Y') }}, so changes show once there’s 30 days of it.
            @endif
        </p>
        <div class="row row-cards">
            @foreach ($stateTrends as $trend)
                <div class="col-6 col-md-4 col-xl-2">
                    <x-stats.tile :label="$trend['state']->name" :value="$trend['mills']" :link="$trend['link']"
                        :detail="null === $trend['change'] ? null : (($trend['change'] > 0 ? '+' : ($trend['change'] < 0 ? '−' : '±')).number_format(abs($trend['change'])).' in 30 days')"
                        :sparkline="$trend['sparkline']" />
                </div>
            @endforeach
        </div>
        <details class="stats-table mt-3">
            <summary>Show the numbers</summary>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-striped mb-0">
                    <caption class="visually-hidden">Mills by state, now and 30 days ago</caption>
                    <thead><tr><th scope="col">State</th><th scope="col" class="num">Mills now</th><th scope="col" class="num">30 days ago</th><th scope="col" class="num">Change</th></tr></thead>
                    <tbody>
                        @foreach ($stateTrends as $trend)
                            <tr>
                                <th scope="row" class="fw-normal">{{ $trend['state']->name }}</th>
                                <td class="num">{{ number_format($trend['mills']) }}</td>
                                <td class="num">{{ null === $trend['then'] ? '–' : number_format($trend['then']) }}</td>
                                <td class="num">{{ null === $trend['change'] ? '–' : number_format($trend['change']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    </div>
</section>

{{-- the numbers stakeholders originally asked for --}}
<section class="card mb-3" aria-labelledby="recent-title">
    <div class="card-body">
        <h2 class="card-title mb-1" id="recent-title">Mills added and updated recently</h2>
        <p class="text-secondary small mb-3">
            Mills first added, and mills last updated, within each period. A mill counts once, by its most recent update.
            Imports count as updates, so a state that re-imported recently shows most of its mills as updated.
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0">
                <caption class="visually-hidden">Mills added and updated in the last 30, 90, 180 and 365 days, by state</caption>
                <thead>
                    <tr>
                        <th scope="col" rowspan="2" class="align-bottom">State</th>
                        <th scope="col" rowspan="2" class="num align-bottom">Mills</th>
                        <th scope="colgroup" colspan="{{ count($windows) }}" class="text-center">Added in the last</th>
                        <th scope="colgroup" colspan="{{ count($windows) }}" class="text-center">Updated in the last</th>
                    </tr>
                    <tr>
                        @foreach (['added', 'updated'] as $kind)
                            @foreach ($windows as $days)
                                <th scope="col" class="num">{{ $days }} days</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recent as $name => $row)
                        <tr>
                            <th scope="row" class="fw-normal">
                                @if ($link = $stateLink($name))
                                    <a href="{{ $link }}">{{ $name }}</a>
                                @else
                                    {{ $name }}
                                @endif
                            </th>
                            <td class="num">{{ number_format($row['mills']) }}</td>
                            @foreach (['added', 'updated'] as $kind)
                                @foreach ($windows as $days)
                                    <td class="num">{{ number_format($row[$kind][$days]) }}</td>
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <th scope="row">All states</th>
                        <td class="num">{{ number_format(array_sum(array_column($recent, 'mills'))) }}</td>
                        @foreach (['added', 'updated'] as $kind)
                            @foreach ($windows as $days)
                                <td class="num">{{ number_format(array_sum(array_map(fn ($r) => $r[$kind][$days], $recent))) }}</td>
                            @endforeach
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</section>

<x-stats.chart :chart="$typesAcrossStates" />

<section class="card mb-3" aria-labelledby="types-heat-title">
    <div class="card-body">
        <h2 class="card-title mb-1" id="types-heat-title">Mill types by state</h2>
        <p class="text-secondary small mb-3">Mills of the region’s ten most common types in each state. Darker cells have more mills.</p>
        <x-stats.heat-table :columns="$typesHeat['columns']" :rows="$typesHeat['rows']" :max="$typesHeat['max']"
            row-heading="State" caption="Mills of each type, by state" />
    </div>
</section>

<div class="row">
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$types" /></div>
    <div class="col-12 col-xl-6">
        <x-stats.chart :chart="$species" />
        <x-stats.chart :chart="$dataAge" />
    </div>
</div>

<x-stats.chart :chart="$completeness">
    <p class="small text-secondary mt-2 mb-0">All states together. Each state’s own numbers are on its By State page.</p>
</x-stats.chart>
@endsection
