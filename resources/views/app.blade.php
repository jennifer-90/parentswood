<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <title>ParentsWood — Communauté de parents solos & événements près de chez vous</title>
        <meta name="description" content="ParentsWood aide les parents solos à se rencontrer autour d’événements conviviaux près de chez eux. Rejoignez une communauté bienveillante." />
        <meta name="keywords" content="parents solos, communauté, événements parents, rencontres, entraide, activités enfants">

        <link rel="canonical" href="https://www.parentswood.be/" />

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css"> -->
        <script src="https://cdn.plot.ly/plotly-2.27.1.min.js"></script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead

    </head>

    <link rel="icon" type="image/png" href="{{ asset('images/default-logo-onglet.ico') }}" sizes="32x32">
    <link rel="icon" type="image/png" href="{{ asset('images/default-logo-onglet.ico') }}" sizes="16x16">
    <link rel="apple-touch-icon" href="{{ asset('images/default-logo-onglet.ico') }}" sizes="180x180">


    <body class="font-sans antialiased">
        @inertia

    </body>



</html>
