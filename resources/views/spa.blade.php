<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
      body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image: url("{{ asset('tea.webp') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(8px);
            transform: scale(1.05); /* prevents blurry edges */
            z-index: -1;
        }

        /* Optional dark overlay for readability */
        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.25);
            z-index: -1;
        }
    </style>

    <script>
        window.__USER__ = @json($user ?? null);
        window.__APP_NAME__ = @json(config('app.name'));
        window.__ICE__ = @json($ice ?? null);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])


</head>

<body class="min-h-screen  ">
    <div id="app"></div>
</body>

</html>