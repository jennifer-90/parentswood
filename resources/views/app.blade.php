<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
            integrity="sha512-TwA5J0itvV23I1Zmc8AkhI+NsZ6Nc8+2bLGdMS0wRMq+RLQ3TD+v8B2V2hU4wXxJg/nKcZ35mxmtMSu3CeFZ6A=="
            crossorigin="anonymous"
            referrerpolicy="no-referrer"
        />

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>

    <body class="font-sans antialiased">
        @inertia
    </body>

    <footer>
        Coucou je suis le footer
        <i class="fa-brands fa-x-twitter"></i>
        <i class="fa-brands fa-facebook"></i>
        <i class="fa-brands fa-tiktok"></i>
        <i class="fa-brands fa-github"></i>
    </footer>

</html>
