@extends(backpack_view('blank'))

@section('content')
<section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
    <h1 class="text-capitalize mb-0" bp-section="page-heading">State Hub</h1>
    <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">Choose a state to manage its page</p>
</section>

<div class="row">
    @foreach ($states as $state)
        <div class="col-6 col-md-3 col-lg-2 mb-3">
            <a class="card card-link h-100" href="{{ route('admin.page.state-hub.show', ['state' => $state]) }}">
                <div class="card-body">{{ $state->name }}</div>
            </a>
        </div>
    @endforeach
</div>
@endsection
