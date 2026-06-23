@extends (backpack_view('blank'))

@section('header')
    <section class="container-fluid" bp-section="page-header">
        <h2>
            <span class="text-capitalize">
                {!! $crud->getHeading() ?? $crud->entity_name_plural !!}
            </span>
            <small>
                {!! $crud->getSubheading() ?? trans('import-operation::import.import').' '.$crud->entity_name_plural !!}
                .
            </small>

            @if ($crud->hasAccess('list'))
                <small>
                    <a href="{{ url($crud->route) }}" class="d-print-none font-sm">
                        <i class="la la-angle-double-{{ config('backpack.base.html_direction') == 'rtl' ? 'right' : 'left' }}"></i>
                        {{ trans('backpack::crud.back_to_all') }}
                        <span>
                            {{ $crud->entity_name_plural }}
                        </span>
                    </a>
                </small>
            @endif
        </h2>
    </section>
@endsection


@section('content')

    <div class="row" bp-section="import-confirm-import">
        <div class="col-md-10">
            {{-- Default box --}}
            @if (!empty($errorMessages))
            <h2>Errors</h2>
            <pre class="text-white">
                @php
                    print_r($errorMessages);
                @endphp
            </pre>
            @endif

            @if (!empty($messages))
            <h2>Messages</h2>
            <pre class="text-white">
                @php
                    print_r($messages);
                @endphp
            </pre>
            @endif

            <h2>Data</h2>
            <pre class="text-white">
                @php
                    print_r($importData);
                @endphp
            </pre>

            <h2>Rules</h2>
            <pre class="text-white">
                @php
                    print_r($rules);
                @endphp
            </pre>
        </div>
    </div>
@endsection
