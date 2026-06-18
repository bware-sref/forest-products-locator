@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('import-operation::import.import') => url($crud->route.'/import'),
      trans('import-operation::import.map_fields') => '#'

    ];

    // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <section class="container-fluid" bp-section="page-header">
        <h2>
            <span class="text-capitalize">
                {!! $crud->getHeading() ?? $crud->entity_name_plural !!}
            </span>
            <small>
                {!! $crud->getSubheading() ?? trans('import-operation::import.map_fields_for').' '.$crud->entity_name_plural !!}
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

    <div class="row" bp-section="import-map-fields">
        <div class="col-md-10">
            {{-- Default box --}}

            @include('crud::inc.grouped_errors')

            <form method="post"
                  action="{{ url($crud->route.'/import/'.$import->id.'/map') }}"
                  enctype="multipart/form-data"
            >
                {!! csrf_field() !!}
                {{-- load the view from the application if it exists, otherwise load the one in the package --}}

                {{-- This makes sure that all field assets are loaded. --}}
                <div class="d-none" id="parentLoadedAssets">{{ json_encode(Basset::loaded()) }}</div>

                <div class="card">
                    <div class="card-body row">
                        <div class="col-md-12">
                            {{-- WTF, h5 after h2?!? --}}
                            <h3>
                                @lang('import-operation::import.map_fields')
                            </h3>
                            {{-- <div class="row">
                                <h4>$column_headers</h4>
                                <pre class="text-white">
                                    @php
                                    print_r($column_headers);
                                    @endphp
                                </pre>
                                <h4>import->config</h4>
                                <pre class="text-white">
                                    @php
                                    print_r($import->config);
                                    @endphp                                    
                                </pre>
                            </div> --}}
                            @include('import-operation::inc.mapper-headings')
                            <div class="border p-1" style="height: 50vh; overflow-y: auto; overflow-x:hidden;">
                                @foreach($crud->columns() as $column)
                                    <div class="row mb-2">
                                        <div class="col-md-6">
                                            <div class="card" style="height: 100%;">
                                                <div
                                                    class="card-body d-flex flex-column justify-content-center py-1 px-3">
                                                    {{-- <pre class="text-white">@php print_r($column); @endphp</pre> --}}
                                                    <div class="form-group">
                                                        <label for="{{ $column['name'] }}__heading">
                                                            @lang('import-operation::import.select_a_column')

                                                            @if(in_array($column['name'], $required_columns))
                                                                <span class="text-danger">*</span>
                                                            @endif
                                                        </label>
                                                        <select class="form-control" {{ in_array($column['name'], $required_columns) ? 'required' : '' }}
                                                                name="{{ $column['name'] }}__heading"
                                                                id="{{ $column['name'] }}__heading">
                                                            <option value="">
                                                                @lang('import-operation::import.dont_import')
                                                            </option>
                                                            @foreach($column_headers as $heading)
                                                                @php
                                                                    $selected = false;
                                                                    $optionLabel = ucwords(str_replace('_', ' ', $heading));
                                                                    /**
                                                                     * I see.
                                                                     * $import->config will usually only be populated here if we
                                                                     * returned from the confirm screen.
                                                                     * Which is strange because...
                                                                     * well, because $column comes from $crud->columns(), which is 
                                                                     * defined in the controller's import() method...
                                                                     * which partially explains why we skip this screen when using a
                                                                     * custom import class.
                                                                     * Using a custom import class causes the import operation to 
                                                                     * ignore any crud columns defined in the controller and thus 
                                                                     * results in both crud->columns and import->config being null.
                                                                     */
                                                                    if(
                                                                        (isset($import->config) && 
                                                                        isset($import->config[$heading]) &&
                                                                        isset($import->config[$heading]['name']) &&
                                                                        $import->config[$heading]['name'] === $column['name']) ||
                                                                        $heading === $column['name'] ||
                                                                        $heading === $column['key'] ||
                                                                        // just to be sure
                                                                        strtolower($heading) === strtolower($column['label'])
                                                                    ){
                                                                        $selected = true;
                                                                        $optionLabel = $column['label'];
                                                                    }
                                                                @endphp
                                                                <option
                                                                    {{ $selected ? 'selected' : '' }}
                                                                    value="{{ $heading }}">
                                                                    {{-- {{ ucfirst(str_replace('_', ' ', $heading)) }} --}}
                                                                    {{-- {{ ucwords(str_replace('_', ' ', $heading)) }} --}}
                                                                    {{ $optionLabel }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        {{-- Spacer --}}
                                        <div class="col-md-2">
                                            <div class="card" style="height: 100%;">
                                                <div
                                                    class="card-body d-flex flex-column align-items-center justify-content-center py-1 px-3">
                                                    <i class="las la-arrow-right hidden d-md-block font-5xl text-muted"></i>
                                                    <i class="las la-arrow-down d-md-none font-5xl text-muted"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div
                                                class="card @if($primary_key === $column['name']) border-primary @endif"
                                                style="height: 100%;">
                                                <div
                                                    class="card-body d-flex flex-column justify-content-center py-1 px-3">
                                                    <p class="text-uppercase text-muted m-0 font-xs">
                                                        {{ $crud->entity_name }}
                                                    </p>
                                                    <h4 class="m-0 font-xl">
                                                        {{ $column['label'] }}
                                                        @if(in_array($column['name'], $required_columns))
                                                            <span class="text-danger">*</span>
                                                        @endif
                                                    </h4>
                                                    <strong class="text-muted">
                                                        @if(in_array($column['type'], array_keys(config('backpack.operations.import.column_aliases'))))
                                                            @php
                                                                $aliases = config('backpack.operations.import.column_aliases');
                                                                $class = $aliases[$column['type']];
                                                            @endphp

                                                            {{ (new $class(''))->getName() }}
                                                        @else
                                                            {{ (new $column['type'](''))->getName() }}
                                                        @endif
                                                        @if($primary_key === $column['name'])
                                                            <small class="text-primary">
                                                                [@lang('import-operation::import.primary_key')]
                                                            </small>
                                                        @endif
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <button title="@lang('import-operation::import.confirm_mapping')"
                        class="btn btn-success">
                    <span class="ladda-label">
                         <i class="las la-check-circle"></i>
                         @lang('import-operation::import.confirm_mapping')
                    </span>
                </button>
            </form>
        </div>
    </div>

@endsection

