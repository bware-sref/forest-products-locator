@extends('admin.statistics.layout')

@php
    $reasonText = [
        'Same words' => 'Same words, written with different punctuation, order or plurals',
        'Abbreviation' => 'One is an abbreviation of the other',
    ];
    $nameCell = function (array $item, callable $editLink) {
        $link = $editLink($item['id']);

        return $link ? '<a href="'.e($link).'">'.e($item['name']).'</a>' : e($item['name']);
    };
    $mapperText = fn (array $item) => $item['mappers'] ? implode(', ', $item['mappers']) : 'None (from source data)';
@endphp

@section('stats')
<div class="alert alert-info" role="note">
    <div>
        Names that may mean the same thing, as candidates to discuss merging. Nothing here changes any data.
        The import mappers set some names in code, and pass every other value through as it appears in a state’s
        source data. A merged name that no mapper mentions would need a new mapper entry, or the next import
        would bring it back.
    </div>
</div>

<nav class="mb-3" aria-label="Lists on this page">
    @foreach ($lists as $key => $list)
        <a href="#{{ $key }}-title" class="btn btn-sm btn-outline-secondary me-1">{{ $list['label'] }}</a>
    @endforeach
</nav>

@foreach ($lists as $key => $list)
    <section class="card mb-3" aria-labelledby="{{ $key }}-title">
        <div class="card-body">
            <h2 class="card-title mb-1" id="{{ $key }}-title">{{ $list['label'] }}: likely the same</h2>
            <p class="text-secondary small mb-3">
                {{ count($list['groups']) }} {{ Str::plural('group', count($list['groups'])) }} among
                {{ number_format($list['count']) }} {{ Str::lower($list['label']) }}. Mills counts every mill
                (published or not) that has the name; a mill can have several.
            </p>

            @forelse ($list['groups'] as $i => $group)
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-striped mb-0">
                        <caption class="caption-top text-body pb-1">
                            <span class="fw-semibold">{{ implode(', ', array_column($group['names'], 'name')) }}</span>
                            <span class="text-secondary small">· {{ implode('; ', array_map(fn ($r) => $reasonText[$r] ?? $r, $group['reasons'])) }}</span>
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col" class="num">Mills</th>
                                <th scope="col" class="num">States</th>
                                <th scope="col">Named in mappers</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['names'] as $item)
                                <tr>
                                    <th scope="row" class="fw-normal">{!! $nameCell($item, $list['editLink']) !!}</th>
                                    <td class="num">{{ number_format($item['mills']) }}</td>
                                    <td class="num">{{ number_format($item['states']) }}</td>
                                    <td class="{{ $item['mappers'] ? '' : 'text-secondary' }}">{{ $mapperText($item) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="mb-0">No {{ Str::lower($list['label']) }} look like the same name written differently.</p>
            @endforelse
        </div>
    </section>

    <section class="card mb-3" aria-labelledby="{{ $key }}-related-title">
        <div class="card-body">
            <h2 class="card-title mb-1" id="{{ $key }}-related-title">{{ $list['label'] }}: possibly related</h2>
            <p class="text-secondary small mb-3">
                Names where every word of one appears in the other, or that differ by a letter or two. Some are the
                same thing; others are a narrower kind (Portable Sawmill is a kind of Sawmill) or a different word
                (Pallet and Pellet). These need a person to judge.
            </p>

            @if ($list['related'])
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <caption class="visually-hidden">{{ $list['label'] }} that are possibly related</caption>
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Similar to</th>
                                <th scope="col">Why</th>
                                <th scope="col" class="num">Mills</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($list['related'] as $pair)
                                <tr>
                                    <th scope="row" class="fw-normal">
                                        @foreach ($pair['first'] as $item)
                                            {!! $nameCell($item, $list['editLink']) !!} <span class="text-secondary">({{ number_format($item['mills']) }})</span>@if (! $loop->last)<br>@endif
                                        @endforeach
                                    </th>
                                    <td>
                                        @foreach ($pair['second'] as $item)
                                            {!! $nameCell($item, $list['editLink']) !!} <span class="text-secondary">({{ number_format($item['mills']) }})</span>@if (! $loop->last)<br>@endif
                                        @endforeach
                                    </td>
                                    <td>{{ 'Spelling' === $pair['reason'] ? 'Differ by a letter or two' : 'One name is within the other' }}</td>
                                    <td class="num">{{ number_format($pair['mills']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0">None found.</p>
            @endif
        </div>
    </section>
@endforeach
@endsection
