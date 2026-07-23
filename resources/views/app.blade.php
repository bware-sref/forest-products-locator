<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
{{-- 
Add Twitter card and Facebook OpenGraph meta tags here
Or do we?
<head> content can be added on individual page templates via the Head component (same thing that sets the title).
--}}
        {{-- Inline style to set the HTML background color based on our theme in app.css --}}        
        <style>
            html {
                background-color: oklch(1 0 0);
            }
        </style>
        {{-- Favicon generator mess --}}
        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="Primary Forest Products Locator" />
        <link rel="manifest" href="/site.webmanifest" />

        {{-- Google Fonts used in Figma designs (also available at fonts.bunny.net) --}}
        <link rel="preconnect" href="https://fonts.bunny.net">
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
