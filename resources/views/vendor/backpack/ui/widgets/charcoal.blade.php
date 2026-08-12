{{--
resources/views/vendor/backpack/ui/widgets/charcoal.blade.php

Charcoal is a good filter.


FFS, Backpack docs, learn your own shit.
More specifically, docs use "backpack::widgets.inc.wrapper_start", but that causes an error because this widget
doesn't know where the fuck that is.
But looking at a widget created via the artisan command, it uses the backpack_view() method instead of backpack::
--}}
@php
    /**
     * @var string
     * The name of the query parameter we're using to filter.
     */
    $filterKey = $widget['filterKey'] ?? 'filter_key';

    $filterLabel = $widget['filterLabel'] ?? 'Filter';

    /**
     * It would be nice if we could know if a form id has been used.
     */
    $formId = ($widget['filterFormId'] ?? 'charcoalio') . '_' . bin2hex(random_bytes(10));

    /**
     * Allow overriding the empty/unselected option value.
     * I feel like this should be prepended to the options array so we can just loop over all of them.
     * Actually, we only need the label for the empty option since the value of empty is empty. :-D
     */
    $widget['emptyOptionLabel'] ??= '-- All --';

    /**
     * I should rename this to options
     */
    if (empty($widget['options'])):
        $widget['options'] = [
            [
                'id' => '',
                'name' => 'No Options?!?',
            ]
        ];
    endif;

    /**
     * We have to explicitly clear the filter value from the query string.
     * Do that by passing null for the param value.
     */
    $clearUrl = request()->fullUrlWithQuery([
        'persistent-table' => false,
        $filterKey => null,
    ]);
@endphp
@includeWhen(!empty($widget['wrapper']), backpack_view('widgets.inc.wrapper_start'))
<div class="row mb-3">
    <div class="col-md-4">
        <form method="GET" action="{{ url()->current() }}" id="{{ $formId }}">
            <!-- Retain existing URL parameters like page, search, or sorting -->
            @foreach(request()->except($filterKey) as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            <div class="input-group">
                <span class="input-group-text"><i class="la la-filter"></i> {{ $filterLabel }}</span>
                <select name="{{ $filterKey }}" class="form-control"
                    onchange="document.getElementById('{{ $formId }}').submit();">
                    <option value="">{{ $widget['emptyOptionLabel'] }}</option>
                    @foreach($widget['options'] as $option)
                        {{-- FFS, strict comparison causes the damn thing to fail --}}
                        <option value="{{ $option['id'] }}" {{ request($filterKey) == $option['id'] ? 'selected' : '' }}>
                            {{ $option['name'] }}
                        </option>
                    @endforeach
                    @if (false)
                        <option value="active" {{ request($filterKey) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request($filterKey) === 'inactive' ? 'selected' : '' }}>Inactive
                        </option>
                    @endif
                </select>

                {{--
                The Clear button seems to run afoul of "persistent table" feature.
                The solution is to clear the "persistent-table" query string variable by passing null.
                Alternatively, we could use jQuery to trigger the "Reset" link in the datatable.
                --}}
                @if(request()->has($filterKey) && request()->input($filterKey) !== '')
                    <a href="{{ $clearUrl }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

@includeWhen(!empty($widget['wrapper']), backpack_view('widgets.inc.wrapper_end'))