@extends(backpack_view('blank'))

@php
    /**
     * Every link carries _backToAllEntriesUrl so the CRUD's "back" link returns here.
     * Add links also pass the parent key (state_id or state_assistance_category_id) so the form can preselect it.
     */
    $back = ['_backToAllEntriesUrl' => $hubUrl];
    $editUrl = fn (string $table, $entry) => backpack_url($segments[$table].'/'.$entry->getKey().'/edit').'?'.http_build_query($back);
    $createUrl = fn (string $table, array $params) => backpack_url($segments[$table].'/create').'?'.http_build_query($params + $back);
    $user = backpack_user();
@endphp

@section('content')
<section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
    <h1 class="text-capitalize mb-0" bp-section="page-heading">{{ $state->name }}</h1>
    <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">State Page sections</p>
</section>

<div class="row">
@foreach ($sections as $section)
    @php
        $table = $section['table'];
        $canSee = $user->canAny(["{$table}.see", "{$table}.edit"]);
        $canEdit = $user->can("{$table}.edit");
        // single-record sections: agents can't create (SetsUpForStateAgents), and nobody needs a second one
        $records = $section['single']
            ? collect([$state->{$section['relation']}])->filter()
            : $state->{$section['relation']};
        $canAdd = $canEdit && (! $section['single'] || ($records->isEmpty() && ! $isStateAgent));
    @endphp

    @continue(! $canSee)

    <div class="col-12 col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">{{ $section['title'] }}</h3>
                @if ($canAdd)
                    <a class="btn btn-sm btn-outline-primary" href="{{ $createUrl($table, ['state_id' => $state->id]) }}">
                        <i class="la la-plus"></i> Add
                    </a>
                @endif
            </div>

            <ul class="list-group list-group-flush">
                @forelse ($records as $record)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>{{ $record->{$section['label']} ?: '(untitled)' }}</span>
                            @if ($canEdit)
                                <a href="{{ $editUrl($table, $record) }}"><i class="la la-edit"></i> Edit</a>
                            @endif
                        </div>

                        {{-- Assistance categories are the only section with children of their own --}}
                        @if ($table === 'state_assistance_categories' && $user->canAny(['state_assistance_links.see', 'state_assistance_links.edit']))
                            <ul class="list-unstyled ms-4 mt-2 mb-0">
                                @foreach ($record->links as $link)
                                    <li class="d-flex justify-content-between">
                                        <span class="text-muted">{{ $link->label }}</span>
                                        @if(backpack_user()->can('state_assistance_links.edit'))
                                            <a href="{{ $editUrl('state_assistance_links', $link) }}"><i class="la la-edit"></i> Edit</a>
                                        @endif
                                    </li>
                                @endforeach
                                @if(backpack_user()->can('state_assistance_links.edit'))
                                    <li>
                                        <a href="{{ $createUrl('state_assistance_links', ['state_assistance_category_id' => $record->getKey()]) }}">
                                            <i class="la la-plus"></i> Add link
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        @endif
                    </li>
                @empty
                    <li class="list-group-item text-muted">Nothing yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endforeach
</div>
@endsection
