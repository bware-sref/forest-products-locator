@extends (backpack_view('blank'))

@section('header')
    <section class="container-fluid" bp-section="page-header">
        
        {{-- <h1>
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
        </h1> --}}

        {{-- The boneheaded, unsemantic structure below pisses me off, but I need better hobbies. --}}
        <h2>
            <span class="text-capitalize">
                {{ $title }}
                {{-- {!! $crud->getHeading() ?? $crud->entity_name_plural !!} --}}
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

    <div class="row g-4" bp-section="import-preview-import">
        <div class="col-md-10">
            {{-- Default box --}}
            @if (!empty($errorMessages))
                <h2>Errors</h2>
                <p class="fs-2">{{ count($errorMessages) }} rows with errors.</p>
                <p>Please correct the errors listed below in your data file and restart the import process to upload the revised version.</p>
                <p>If you choose to proceed with this import without correcting the data first, the rows which have errors will be omitted from the import.</p>
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
            @else
                <div>
                    <p class="fs-2">{{ $rowCount }} rows found, no errors.</p>
                </div>
            @endif

            {{-- The post route for import/{id}/confirm initiates the import. --}}
            <form method="post"
                  action="{{ url($crud->route.'/import/'.$import->id.'/confirm') }}"
                  enctype="multipart/form-data"
                  id="import-preview"
            >
                {!! csrf_field() !!}
                {{-- <div class="card">
                    <p>What happens in a card?</p>
                </div> --}}
                {{-- This makes sure that all field assets are loaded. --}}
                <div class="d-none" id="parentLoadedAssets">{{ json_encode(Basset::loaded()) }}</div>
                {{-- Buttons! --}}
                <div class="d-flex g-4">
                    <a title="@lang('import-operation::import.start_over')"
                        class="btn btn-secondary me-2"
                        href="{{ url($crud->route.'/import') }}">
                        <span class="ladda-label">
                            <i class="las la-times-circle"></i>
                            @lang('import-operation::import.start_over')
                        </span>
                    </a>
                    <button title="@lang('import-operation::import.start_import')"
                            class="btn btn-success me-2 justify-end position-relative"
                            id="start-import"
                    >
                        <span class="ladda-label">
                            <i class="las la-file-upload"></i>
                            @lang('import-operation::import.start_import')
                        </span>
                        <span class="loader position-absolute" style="display: none;"></span>
                    </button>
                    <span class="loader" style="display: none;"></span>
                </div>
            </form>

            {{-- <h2>Mill Import Preview</h2> --}}
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
                                        <td>{{ $row[$key] ?? '' }}</td>
                                    @endforeach
                                </tr>                            
                            @else
                                {{-- <tr class="error table-danger border-danger">
                                    <th scope="row">{{ $index + 2 }}</th>
                                    <td colspan="{{ $columnCount }}">
                                        <div class="border-danger h-100 w-100">
                                            @foreach($rowErrors as $rowError)
                                            <span class="d-block text-danger">{{ $rowError }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr> --}}
                                <tr class="error table-danger">
                                    <th scope="row">
                                        {{ $index + 2 }}
                                        <i class="fs-1 la la-exclamation-triangle text-danger"></i>
                                    </th>
                                    @foreach ($columns as $key)
                                        @php
                                            $cellClass = !empty($rowErrors[$key]) ? 'p-0' : '';
                                        @endphp
                                        <td class="{{ $cellClass }}">
                                            @if (!empty($rowErrors[$key]))
                                                <div class="text-danger border border-danger w-100 h-100 p-3" id="error-{{ (string) ($index + 2) . '-' . $key}}">
                                                    {{ $row[$key] ?? '' }}
                                                    <br />                                                    
                                                    <strong class="fw-bold">{{ $rowErrors[$key] }}</strong>
                                                </div>
                                            @else
                                                {{ $row[$key] ?? '' }}                                            
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
