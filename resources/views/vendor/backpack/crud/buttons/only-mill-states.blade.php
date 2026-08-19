@if (true)
    @php
        $params = ['all' => true];
        $btnText = 'All States';
        if ($crud->getRequest()->has('all')) {
            $params = [
                'all' => null,
                'persistent-table' => false,
            ];
            $btnText = 'Only Mill States';
        }

    @endphp
    <a href="{{ url()->query($crud->route, $params) }}" bp-button="only-mill-states" class="btn btn-outline-primary"
        data-style="zoom-in">
        <i class="la la-arrows"></i> <span>{{ $btnText }}</span>
    </a>
@endif