<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#08142b">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Жілік сындыру')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:500,600,700,800,900&display=swap" rel="stylesheet">
    @isset($zhilik)
        <script>
            window.ZHILIK = @json($zhilik);
        </script>
    @endisset
    @vite(array_values(array_filter(['resources/css/app.css', $entry ?? null])))
</head>
<body class="@yield('bodyClass')">
    @yield('content')
</body>
</html>
