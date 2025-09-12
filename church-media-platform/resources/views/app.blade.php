<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'ForWorship') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">

        <!-- Meta tags for SEO and social sharing -->
        <meta name="description" content="Church Media Platform - Manage your church's videos, live streams, and content">
        <meta name="keywords" content="church, media, streaming, videos, ministry">
        <meta name="author" content="ForWorship">
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ config('app.name', 'ForWorship') }}">
        <meta property="og:description" content="Church Media Platform - Manage your church's videos, live streams, and content">
        
        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:title" content="{{ config('app.name', 'ForWorship') }}">
        <meta property="twitter:description" content="Church Media Platform - Manage your church's videos, live streams, and content">

        <!-- Scripts -->
        @routes('ziggy')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        <!-- Additional Scripts -->
        <script>
            // Global configuration
            window.App = {
                name: @json(config('app.name')),
                url: @json(config('app.url')),
                environment: @json(config('app.env')),
            };

            // CSRF token for axios
            window.axios = window.axios || {};
            window.axios.defaults = window.axios.defaults || {};
            window.axios.defaults.headers = window.axios.defaults.headers || {};
            window.axios.defaults.headers.common = window.axios.defaults.headers.common || {};
            window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        </script>
    </body>
</html>