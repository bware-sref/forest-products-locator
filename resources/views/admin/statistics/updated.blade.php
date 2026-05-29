@extends(backpack_view('blank'))

@php
    // $widgets['after_content'][] = [
    //     'type' => 'card',
    //     'content' => [
    //         'header' => 'Total Mills',
    //         'body' => $millCounts['total'],
    //     ],
    //     'wrapper' => [
    //         'class' => 'com-sm-6 col-md-2',
    //         'style' => '', // 'border-radius: 10px;',
    //     ],
    //     'class' => 'card bg-primary',
    // ];

    $bgs = [
        // 'primary',
        // 'secondary',
        'success',
        'warning',
        'dark',
        'info',
        'danger',
        // 'light',
        // 'primary',
    ];

    // foreach ($millCounts['byState'] as $state) {
    //     $widgets['after_content'][] = [
    //         'type' => 'card',
    //         'content' => [
    //             'header' => $state['name'],
    //             'body' => $state['mills_count'],
    //         ],
    //         'wrapper' => [
    //             'class' => 'col-sm-6 col-md-2',
    //             'style' => '',
    //         ],
    //         'class' => 'card bg-'. next($bgs),
    //     ];
    // }

    // put total at the front of the list
    // array_unshift($millCounts['byState'], ['name' => 'Total Mills', 'mills_count' => $millCounts['total']]);

@endphp

@section('content')
<section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
    <h1 class="text-capitalize mb-0" bp-section="page-heading">Mill Updates</h1>
    <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">Stats about Mills which have been updated in the timeframes listed below.</p>
</section>
<section class="content container-fluid animated fadeIn" bp-section="content">
        @if(false)
        <pre>
        @php
            print_r($millCounts);
        @endphp
        </pre>
        @endif
@if(true)        
    @foreach ($millCounts as $tfLabel => $stats)
    <div class="row gap-1 mb-2">
        <div class="col-2">
            <div class="card text-bg-primary">
                <div class="card-title px-2"><h2>In the {{ $tfLabel }}</h2></div>
                <div class="card-body">            
                    <p>Since {{ $stats['since'] }}</p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="container">
                <div class="row">
                    <div class="col-2">
                        <div class="card text-bg-primary">
                            <div class="card-title px-2">Total</div>
                            <div class="card-body">
                                Quantity: {{ $stats['total']['number'] }}
                                <br>
                                Percentage: {{ sprintf('%.2f', $stats['total']['percentage']) }}%
                            </div>
                        </div>
                    </div>
        @if(!empty($stats['byState']))
            @foreach ($stats['byState'] as $state => $byState)
                @php
                $bg = array_shift($bgs);
                @endphp
                    <div class="col-2 mb-2">
                        <div class="card text-bg-{{ $bg }}">
                            <div class="card-title px-2">{{ $state }}</div>
                            <div class="card-body flex flex-row">
                                <div>
                                    Quantity: {{ $byState['number'] }}
                                    <br>
                                    Percentage: {{ sprintf('%.2f', $byState['percentage']) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                @php
                $bgs[] = $bg;
                @endphp
            @endforeach
        @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
@endif    
@endsection
