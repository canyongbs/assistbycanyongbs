<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    />
    <meta
        name="theme-color"
        content="#000000"
    />
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css"
        rel="stylesheet"
        integrity="sha512-HK5fgLBL+xu6dm/Ii3z4xhlSUyZgTT9tuc/hSrtw6uzJOvgRr2a9jyxxT1ely+B+xFAmJKVSTbpM/CuL7qxO8w=="
        crossorigin="anonymous"
    />
    <link
        href="{{ asset('css/app.css') }}"
        rel="stylesheet"
    />
    <title>{{ trans('panel.site_title') }}</title>

    <script
        src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.8.2/dist/alpine.min.js"
        defer
    ></script>
    @livewireStyles
    @stack('styles')
</head>

<body class="text-blueGray-800 antialiased">

    <noscript>You need to enable JavaScript to run this app.</noscript>

    <div id="app">
        <x-sidebar />

        <div class="relative min-h-screen bg-blueGray-50 md:ml-64">
            <x-nav />

            <div class="relative bg-pink-600 pb-32 pt-12 md:pt-32">
                <div class="mx-auto w-full px-4 md:px-10">&nbsp;</div>
            </div>

            <div class="relative -m-48 mx-auto min-h-full w-full px-4 md:px-10">
                @if (session('status'))
                    <x-alert
                        role="alert"
                        message="{{ session('status') }}"
                        variant="indigo"
                    />
                @endif

                @yield('content')

                <x-footer />
            </div>
        </div>

    </div>

    <form
        id="logoutform"
        style="display: none;"
        action="{{ route('logout') }}"
        method="POST"
    >
        {{ csrf_field() }}
    </form>
    <script
        type="text/javascript"
        src="{{ asset('js/app.js') }}"
    ></script>
    <script src="https://unpkg.com/@popperjs/core@2/dist/umd/popper.js"></script>
    @livewireScripts
    @yield('scripts')
    @stack('scripts')
    <script>
        function closeAlert(event) {
            let element = event.target;
            while (element.nodeName !== "BUTTON") {
                element = element.parentNode;
            }
            element.parentNode.parentNode.removeChild(element.parentNode);
        }
    </script>
</body>

</html>
