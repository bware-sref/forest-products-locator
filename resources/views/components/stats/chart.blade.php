@props(['chart'])
{{--
  A Statistics chart card: title, description, the chart (drawn by public/assets/js/admin/statistics.js), any note,
  and the same numbers in a table, so nothing depends on seeing the chart.
  Extra content (links, small tables) can go in the slot.
--}}
@php
    $config = $chart->toArray();
    $table = $chart->table();
    $describedBy = 'chart-'.$config['id'].'-desc';
@endphp
<figure {{ $attributes->merge(['class' => 'card stats-chart mb-3']) }} id="chart-{{ $config['id'] }}">
    <div class="card-body">
        <figcaption>
            <h2 class="card-title mb-1">{{ $config['title'] }}</h2>
            @if ($config['description'])
                <p class="text-secondary small mb-2" id="{{ $describedBy }}">{{ $config['description'] }}</p>
            @endif
        </figcaption>

        @if ($config['isEmpty'])
            <p class="text-secondary mb-0">{{ $config['empty'] ?? 'No data yet.' }}</p>
        @else
            <div class="stats-canvas" style="height: {{ $config['height'] }}px" data-stats-chart="{{ json_encode($config) }}">
                <canvas role="img" aria-label="{{ $config['title'] }}. The numbers are in the table below the chart."
                    @if ($config['description']) aria-describedby="{{ $describedBy }}" @endif></canvas>
            </div>
        @endif

        @if ($config['note'])
            <p class="small text-secondary mt-2 mb-0">{{ $config['note'] }}</p>
        @endif

        @if ($config['showTable'] && ! $config['isEmpty'])
            <details class="stats-table mt-2">
                <summary>Show the numbers</summary>
                <div class="table-responsive mt-2">
                    <table class="table table-sm table-striped mb-0">
                        <caption class="visually-hidden">{{ $config['title'] }}</caption>
                        <thead>
                            <tr>
                                @foreach ($table['columns'] as $i => $column)
                                    <th scope="col" @class(['num' => $i > 0])>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($table['rows'] as $row)
                                <tr>
                                    @foreach ($row as $i => $cell)
                                        @if ($i === 0)
                                            <th scope="row" class="fw-normal">{{ $cell }}</th>
                                        @else
                                            <td class="num">{{ $cell }}</td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @endif

        {{ $slot }}
    </div>
</figure>
