@props(['columns', 'rows', 'max', 'rowHeading' => '', 'unit' => '', 'caption' => '', 'links' => []])
{{--
  A table whose number cells are shaded by value (one blue ramp, darker = more), for grids like states × mill types.
  The numbers are always printed, so the shading only helps scanning.
  rows: row label => list of values, one per column. links: row label => URL for that row's heading.
--}}
@php
    $step = function ($value) use ($max) {
        if (! $value || $max <= 0) {
            return 0;
        }

        return max(1, min(5, (int) ceil($value / $max * 5)));
    };
@endphp
<div class="table-responsive">
    <table class="table table-sm table-bordered stats-heat mb-0">
        @if ($caption)
            <caption class="visually-hidden">{{ $caption }}</caption>
        @endif
        <thead>
            <tr>
                <th scope="col">{{ $rowHeading }}</th>
                @foreach ($columns as $column)
                    <th scope="col" class="num">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $label => $values)
                <tr>
                    <th scope="row" class="fw-normal text-nowrap">
                        @if (! empty($links[$label]))
                            <a href="{{ $links[$label] }}">{{ $label }}</a>
                        @else
                            {{ $label }}
                        @endif
                    </th>
                    @foreach ($values as $value)
                        <td class="heat heat-{{ $step($value) }}">{{ null === $value ? '–' : \App\Statistics\Chart::format($value).$unit }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
