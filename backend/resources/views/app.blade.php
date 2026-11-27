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

    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '1005369632636183');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=1005369632636183&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 min-h-screen">
    <!-- React Root Entry Point -->
    <div id="app"></div>
</body>
</html>
