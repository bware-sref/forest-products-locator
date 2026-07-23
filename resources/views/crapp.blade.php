<!DOCTYPE html>
{{-- I attempted to disable dark mode because we only have one color scheme but there are myriad media queries that directly poll the OS for prefers dark which this does not affect. --}}
{{-- <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])> --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        {{-- Again, it is the legend... --}}
        {{--
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>
        --}}

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }
        </style>
            {{-- 
            html.dark {
                background-color: oklch(0.145 0 0);
            }
            --}}

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

{{--
        default favicon mess
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
--}}
        {{-- Favicon generator mess --}}
        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="Primary Forest Products Locator" />
        <link rel="manifest" href="/site.webmanifest" />

        {{-- Google Fonts used in Figma designs (also available at fonts.bunny.net) --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
        {{-- bunny fonts used in Laravel starter kit 
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        --}}
        {{-- bunny fonts lato and Zilla Slab --}}
        <link href="https://fonts.bunny.net/css?family=lato:400,400i,700,700i,900,900i|zilla-slab:700" rel="stylesheet" />
        
        {{-- Google versions of Lato and Zilla Slab
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Zilla+Slab:wght@700&display=swap" rel="stylesheet">
        --}}

        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
