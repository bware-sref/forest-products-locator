@extends('admin.statistics.layout')

@section('stats-filter')
    <x-stats.state-filter :states="$states" :selected="$selected" :action="backpack_url('statistics/submissions')" all-label="All states" />
@endsection

@section('stats')
<p class="text-secondary">
    Suggestions from the public website: new mills, and corrections to listed mills.
    {{ $selected ? 'Showing '.$selected->name.'.' : 'Showing all states together.' }}
</p>

<x-stats.kpis :kpis="$kpis" />

<div class="row">
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$received" /></div>
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$reviewed" /></div>
</div>

<div class="row">
    <div class="col-12 col-xl-6">
        <x-stats.chart :chart="$pendingAges" />
        <section class="card mb-3" aria-labelledby="oldest-title">
            <div class="card-body">
                <h2 class="card-title mb-1" id="oldest-title">Waiting longest</h2>
                <p class="text-secondary small mb-3">Pending submissions, oldest first. Each opens the same review page as the notification email.</p>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <caption class="visually-hidden">Pending submissions, oldest first</caption>
                        <thead>
                            <tr>
                                <th scope="col">Mill</th>
                                <th scope="col">Kind</th>
                                @unless ($selected)<th scope="col">State</th>@endunless
                                <th scope="col" class="num">Received</th>
                                <th scope="col" class="num">Waiting</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($oldestPending as $row)
                                <tr>
                                    <th scope="row" class="fw-normal">
                                        @if ($row->review_hash)
                                            <a href="{{ route('mill-edits.show', $row->review_hash) }}">{{ $row->mill_name ?: 'Unnamed mill' }}</a>
                                        @else
                                            {{ $row->mill_name ?: 'Unnamed mill' }}
                                        @endif
                                    </th>
                                    <td>{{ $row->is_new ? 'New mill' : 'Correction' }}</td>
                                    @unless ($selected)<td>{{ $stateNames[$row->state_id] ?? '–' }}</td>@endunless
                                    <td class="num">{{ $row->created_at?->format('M j, Y') }}</td>
                                    <td class="num">{{ $row->created_at ? number_format((int) $row->created_at->diffInDays(now())).' days' : '–' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-secondary">Nothing is waiting for review.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-6"><x-stats.chart :chart="$fields" /></div>
</div>
@endsection
