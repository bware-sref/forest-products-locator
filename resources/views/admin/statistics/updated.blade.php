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
        'primary',
        'secondary',
        'success',
        'warning',
        'info',
        'danger',
        // 'light',
        'dark',
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
    <h1 class="text-capitalize mb-0" bp-section="page-heading">Recently Updated Mills</h1>
    <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">Mills which have been updated recently</p>
</section>
<section class="content container-fluid animated fadeIn" bp-section="content">
    <div class="row gap-2">
        <pre>
        @php
            print_r($millCounts);
        @endphp
        </pre>
@if(false)        
    @foreach ($millCounts['byState'] as $state)
        @php
        $bg = array_shift($bgs);
        @endphp

        <div class="col-md-2">
            <div class="card bg-{{ $bg }}">
                <div class="card-title px-2">{{ $state['name'] }}</div>
                <div class="card-body">{{ $state['mills_count'] }}</div>
            </div>
        </div>
        @php
        $bgs[] = $bg;
        @endphp
    @endforeach
@endif    
    </div>
    @if(false)
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    Go to <code>{{ $page }}</code> to edit this view or <code>{{ $controller }}</code> to edit the controller.
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection
