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
    <script>
        window.__USER__ = @json($user);
        window.__APP_NAME__ = @json(config('app.name'));
        window.__ICE__ = @json($ice);
    </script>
    <style id="boot">
        body {
            visibility: hidden;
        }
    </style>
    <script>
        setTimeout(function() {
            var b = document.getElementById('boot');
            if (b) b.remove();
        }, 3000);
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen">
    <div id="app"></div>
</body>

</html>