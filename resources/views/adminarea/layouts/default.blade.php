<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', config('app.name'))</title>

    {{-- Meta Data --}}
    @include('cortex/foundation::adminarea.partials.meta')
    @stack('head-elements')

    {{-- Styles --}}
    <link href="{{ mix('css/vendor.css') }}" rel="stylesheet">
    <link href="{{ mix('css/theme-adminarea.css') }}" rel="stylesheet">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    @stack('styles')
    @livewireStyles

    {{-- Scripts --}}
    <script>
        window.Laravel = @json(['csrfToken' => csrf_token()]);
        window.Cortex = @json(['accessarea' => request()->accessarea(), 'routeDomains' => default_route_domains()]);
    </script>
    <script src="{{ mix('js/manifest.js') }}" defer></script>
    <script src="{{ mix('js/vendor.js') }}" defer></script>
    @stack('vendor-scripts')
    <script src="{{ mix('js/app.js') }}" defer></script>
</head>
<body class="hold-transition skin-blue fixed sidebar-mini">

    {{-- Main content --}}
    <div id="app" class="wrapper">

        @include('cortex/foundation::adminarea.partials.header')
        @include('cortex/foundation::adminarea.partials.sidebar')

        @yield('content')

        @include('cortex/foundation::adminarea.partials.footer')

    </div>

    {{-- Scripts --}}
    @stack('inline-scripts')
    @livewireScripts
    <script src="{{ route('frontarea.cortex.foundation.turbo.js') }}" data-turbolinks-eval="false" data-turbo-eval="false"></script>

    {{-- Alerts --}}
    @alerts('default')
</body>
</html>
