@props(['label', 'value', 'detail' => null, 'link' => null, 'sparkline' => null])
{{-- A stat tile: label, value, optional detail line, optional link, optional sparkline (decorative; its numbers are
     in a table elsewhere on the page). --}}
<div {{ $attributes->merge(['class' => 'card stats-tile h-100']) }}>
    <div class="card-body py-3">
        <div class="stats-label small">{{ $label }}</div>
        <div class="stats-value">
            @if ($link)
                <a href="{{ $link }}" class="text-reset">{{ is_int($value) ? number_format($value) : $value }}</a>
            @else
                {{ is_int($value) ? number_format($value) : $value }}
            @endif
        </div>
        @if ($detail)
            <div class="small text-secondary">{{ $detail }}</div>
        @endif
        @if ($sparkline && ! $sparkline->isEmpty())
            <div class="stats-spark" aria-hidden="true" data-stats-chart="{{ json_encode($sparkline) }}"><canvas></canvas></div>
        @endif
        {{ $slot }}
    </div>
</div>
