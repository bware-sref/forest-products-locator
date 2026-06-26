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
            <p>{{ count($errorMessages) }} rows with errors.</p>
            <ul>
                @foreach($errorMessages as $rowIndex => $rowErrors)
                <li>
                    <h3>Row #{{ $rowIndex + 2 }}</h3>
                    <ul>
                        @foreach($rowErrors as $errField => $rowErr)
                            <li>
                                <a href="#error-{{ ($rowIndex + 2) . '-' . $errField }}">{{ $rowErr }}</a>
                            </li>
                        @endforeach
                    </ul>
                </li>
                @endforeach
            </ul>
                @if(false)
                <pre class="text-white">
                    @php
                        print_r($errorMessages);
                    @endphp
                </pre>
                @endif
            @endif

            @if (false && !empty($attributes)) 
                <h2>Attributes</h2>
                <pre class="text-white">
                    @php
                        print_r($attributes);
                    @endphp
                </pre>
            @endif

            @if (false && !empty($rules)) 
                <h2>Rules</h2>
                <pre class="text-white">
                    @php
                        print_r($rules);
                    @endphp
                </pre>
            @endif
            

            @if (false && !empty($fieldMap)) 
                <h2>Field Map</h2>
                <pre class="text-white">
                    @php
                        print_r($fieldMap);
                    @endphp
                </pre>
            @endif

            <h2>Data</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <th scope="col">Row #</th>
                        @foreach ($columns as $col)
                            <th scope="col">{{ $col }}</th>                        
                        @endforeach
                    </thead>
                    <tbody>
                        @foreach ($importData as $index => $row)
                            @php
                                // if we don't have errors, we can plow through without checking for the problematic column(s)
                                $rowErrors = $errorMessages[$index] ?? false;
                                // $rowClass = empty($errorMessages[$index]) ? 'valid' : 'error';
                            @endphp
                            @if (!$rowErrors)
                                <tr class="valid table-success">
                                    <th scope="row">{{ $index + 2 }}</th>
                                    @foreach ($columns as $key)
                                        <td>{{ $row[$key] }}</td>
                                    @endforeach
                                </tr>                            
                            @else
                                <tr class="error table-danger border-danger">
                                    <th scope="row">{{ $index + 2 }}</th>
                                    <td colspan="{{ $columnCount }}">
                                        <div class="border-danger h-100 w-100">
                                            @foreach($rowErrors as $rowError)
                                            <span class="d-block text-danger">{{ $rowError }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                <tr class="error table-danger">
                                    <th scope="row">{{ $index + 2 }}</th>
                                    @foreach ($columns as $key)
                                        @php
                                            $cellClass = !empty($rowErrors[$key]) ? 'border-danger' : '';
                                        @endphp
                                        <td class="{{ $cellClass }}">
                                            {{ $row[$key] }}
                                            @if (!empty($rowErrors[$key]))
                                                <div class="text-danger" id="error-{{ (string) ($index + 2) . '-' . $key}}">{{ $rowErrors[$key] }}</div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (false)
            <h2>Rules</h2>
            <pre class="text-white">
                @php
                    print_r($rules);
                @endphp
            </pre>
            @endif
        </div>
    </div>
@endsection
