@extends('admin.statistics.layout')

@section('stats-filter')
    <x-stats.state-filter :states="$states" :selected="$selected" :action="backpack_url('statistics/activity')" all-label="All states" />
@endsection

@section('stats')
<p class="text-secondary">
    Changes recorded in the activity log, by where they came from.
    @if ($firstEntry)
        The log started {{ $firstEntry->format('M j, Y') }}, so earlier changes aren’t counted.
    @else
        Nothing has been logged yet.
    @endif
    {{ $selected ? 'Showing changes to '.$selected->name.'’s mills and imports.' : 'Showing everything.' }}
</p>

<x-stats.kpis :kpis="$kpis" />

<div class="row">
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$bySource" /></div>
    <div class="col-12 col-xl-6">
        @if ($byKind)
            <x-stats.chart :chart="$byKind" />
        @else
            <x-stats.chart :chart="$imports" />
        @endif
    </div>
</div>

<div class="row">
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$fields" /></div>
    <div class="col-12 col-xl-6">
        @if ($byKind)
            <x-stats.chart :chart="$imports" />
        @endif
        <section class="card mb-3" aria-labelledby="imports-title">
            <div class="card-body">
                <h2 class="card-title mb-1" id="imports-title">Recent imports</h2>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <caption class="visually-hidden">Recent imports</caption>
                        <thead>
                            <tr>
                                <th scope="col">Started</th>
                                @unless ($selected)<th scope="col">State</th>@endunless
                                <th scope="col">Status</th>
                                <th scope="col" class="num">Rows</th>
                                <th scope="col">By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentImports as $import)
                                <tr>
                                    <td>{{ $import->created_at?->format('M j, Y') }}</td>
                                    @unless ($selected)<td>{{ $import->state?->name ?? '–' }}</td>@endunless
                                    <td>{{ ucfirst($import->status?->value ?? (string) $import->status) }}</td>
                                    <td class="num">{{ number_format($import->total_rows) }}</td>
                                    <td>{{ $import->user?->name ?? '–' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-secondary">No imports yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<section class="card mb-3" aria-labelledby="recent-changes-title">
    <div class="card-body">
        <h2 class="card-title mb-1" id="recent-changes-title">Latest changes</h2>
        @if ($canSeeLog)
            <p class="text-secondary small mb-3"><a href="{{ backpack_url('activity-log') }}">Open the full activity log</a></p>
        @endif
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <caption class="visually-hidden">Latest logged changes</caption>
                <thead>
                    <tr>
                        <th scope="col">When</th>
                        <th scope="col">Record</th>
                        <th scope="col">Change</th>
                        <th scope="col">Source</th>
                        <th scope="col">By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent as $entry)
                        <tr>
                            <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($entry->created_at)->format('M j, Y g:i a') }}</td>
                            <td>{{ $entry->kind }} #{{ $entry->subject_id }}</td>
                            <td>
                                @if ($canSeeLog)
                                    <a href="{{ backpack_url('activity-log/'.$entry->id.'/show') }}">{{ $entry->description ?: $entry->event }}</a>
                                @else
                                    {{ $entry->description ?: $entry->event }}
                                @endif
                            </td>
                            <td>{{ $entry->source }}</td>
                            <td>{{ $entry->causer ?? '–' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-secondary">Nothing has been logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
