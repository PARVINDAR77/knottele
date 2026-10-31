<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>Nuts & Nutrition | Premium Dry Fruits & Seeds</title>
    <meta name="description" content="Nuts & Nutrition offers premium quality dry fruits, seeds, and nutrition powders. Shop online for the finest natural ingredients.">
    <meta name="theme-color" content="#68b348">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo-cropped.png') }}?v=2">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Vite Scripts & Styles -->
    @if (file_exists(public_path('build/manifest.json')))
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/main.jsx'])
    @endif
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 min-h-screen">
    <!-- React Root Entry Point -->
    <div id="app"></div>
</body>
</html>
