@extends('admin.statistics.layout')

@section('stats')
<div class="alert alert-info" role="note">
    {{-- Tabler's alert is a flex row; one wrapper keeps the text flowing as a paragraph --}}
    <div>
        This page puts states side by side on data completeness, data age, review times and page content. Each state
        works with its own budget and staff, so differences here say little about effort. It's visible only to users
        with the <code>statistics.compare_states</code> permission.
    </div>
</div>

{{-- completeness grid --}}
<section class="card mb-3" aria-labelledby="grid-title">
    <div class="card-body">
        <h2 class="card-title mb-1" id="grid-title">Data completeness by state</h2>
        <p class="text-secondary small mb-3">Share of each state’s mills with each field filled in. Darker cells are more complete.</p>
        @php
            $columns = array_merge(['Every key field'], array_map(fn ($f) => $f[0], array_values($fields)));
            $rows = [];
            $links = [];
            foreach ($states as $state) {
                $c = $completeness[$state->id] ?? null;
                $rows[$state->name] = array_merge(
                    [\App\Statistics\MillStats::percent($c['complete'] ?? 0, $c['total'] ?? 0)],
                    array_map(fn ($key) => $c['fields'][$key]['percent'] ?? 0, array_keys($fields)),
                );
                $links[$state->name] = $stateLink($state);
            }
        @endphp
        <x-stats.heat-table :columns="$columns" :rows="$rows" :max="100" unit="%" :links="$links"
            row-heading="State" caption="Share of mills with each field, by state" />
    </div>
</section>

<div class="row">
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$complete" /></div>
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$age" /></div>
</div>

<section class="card mb-3" aria-labelledby="complete-trends-title">
    <div class="card-body">
        <h2 class="card-title mb-1" id="complete-trends-title">Completeness over the last 90 days</h2>
        <p class="text-secondary small mb-3">Share of each state’s mills with every key field, daily.</p>
        <div class="row row-cards">
            @foreach ($trends as $trend)
                <div class="col-6 col-md-4 col-xl-2">
                    <x-stats.tile :label="$trend['state']->name" :value="null === $trend['now'] ? '–' : \App\Statistics\Chart::format($trend['now']).'%'"
                        :detail="null === $trend['first'] ? null : 'from '.\App\Statistics\Chart::format($trend['first']).'%'"
                        :sparkline="$trend['sparkline']" />
                </div>
            @endforeach
        </div>
    </div>
</section>

<div class="row">
    <div class="col-12 col-xl-7">
        <section class="card mb-3" aria-labelledby="review-title">
            <div class="card-body">
                <h2 class="card-title mb-1" id="review-title">Public submissions by state</h2>
                <p class="text-secondary small mb-3">Review times are medians over the last year.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <caption class="visually-hidden">Public submissions by state</caption>
                        <thead>
                            <tr>
                                <th scope="col">State</th>
                                <th scope="col" class="num">Pending</th>
                                <th scope="col" class="num">Oldest waiting</th>
                                <th scope="col" class="num">Typical review</th>
                                <th scope="col" class="num">Approved</th>
                                <th scope="col" class="num">Rejected</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($states as $state)
                                @php $s = $submissions[$state->id] ?? null; @endphp
                                <tr>
                                    <th scope="row" class="fw-normal">{{ $state->name }}</th>
                                    <td class="num">{{ number_format($s['pending'] ?? 0) }}</td>
                                    <td class="num">{{ isset($s['oldest_days']) ? number_format($s['oldest_days']).' days' : '–' }}</td>
                                    <td class="num">{{ isset($s['median']) ? \App\Statistics\Chart::format($s['median']).' days' : '–' }}</td>
                                    <td class="num">{{ number_format($s['approved'] ?? 0) }}</td>
                                    <td class="num">{{ number_format($s['rejected'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-5">
        <section class="card mb-3" aria-labelledby="content-title">
            <div class="card-body">
                <h2 class="card-title mb-1" id="content-title">State page content</h2>
                <p class="text-secondary small mb-3">Sections with content (of {{ $sectionCount }}) and the last change to any of them.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <caption class="visually-hidden">State page content by state</caption>
                        <thead><tr><th scope="col">State</th><th scope="col" class="num">Sections</th><th scope="col" class="num">Last changed</th></tr></thead>
                        <tbody>
                            @foreach ($states as $state)
                                <tr>
                                    <th scope="row" class="fw-normal">{{ $state->name }}</th>
                                    <td class="num">{{ $content[$state->id]['filled'] }}</td>
                                    <td class="num">{{ $content[$state->id]['updated']?->format('M j, Y') ?? '–' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
