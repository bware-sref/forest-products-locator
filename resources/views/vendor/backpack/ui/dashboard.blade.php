@extends(backpack_view('blank'))

@php
    if (backpack_theme_config('show_getting_started')) {
        $widgets['before_content'][] = [
            'type'        => 'view',
            'view'        => backpack_view('inc.getting_started'),
        ];
    } else {
        $widgets['before_content'][] = [
            'type'        => 'jumbotron',
            // trans() is the translate method
            // backpack::base.welcome corresponds to the 'welcome' array key in /vendor/backpack/crud/src/resources/lang/en/base.php
            'heading'     => trans('backpack::base.welcome'),
            'heading_class' => 'display-3 '.(backpack_theme_config('layout') === 'horizontal_overlap' ? ' text-white' : ''),
            'content'     => 'What sidebar?', //trans('backpack::base.use_sidebar'),
            'content_class' => backpack_theme_config('layout') === 'horizontal_overlap' ? 'text-white' : '',
            'button_link' => backpack_url('logout'),
            'button_text' => trans('backpack::base.logout'),
        ];

        $widgets['before_content'][] = [
            'type' => 'chip',
            'view' => 'crud::chips.general',
            'title' => 'Chip!',
            'entry' => 'huh?',
        ];



        $bgs = [
            'primary',
            'secondary',
            'success',
            'warning',
            'info',
            'danger',
            'light',
            'dark',

        ];

        foreach ($bgs as $bg) {
            $widgets['before_content'][] = [
                'type' => 'card',
                'content' => [
                    'header' => 'I am a '.$bg.' card header!',
                    'body' => 'I am a '.$bg.' card body!',
                ],
                'wrapper' => [
                    'class' => 'com-sm-6 col-md-4',
                    'style' => '', // 'border-radius: 10px;',
                ],
                'class' => 'card bg-' . $bg,
            ];
        }

    }
@endphp

@section('content')
@endsection
