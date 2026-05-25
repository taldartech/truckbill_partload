<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- FontAwesome for AdminLTE Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- OverlayScrollbars CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css">
    
    <!-- AdminLTE Theme CSS -->
    <link href="/theme/dist/css/adminlte.css" rel="stylesheet">
    
    <!-- Custom CSS from previous project -->
    <link href="/css/brand.css" rel="stylesheet">
    <link href="/css/buttons.css" rel="stylesheet">
    <link href="/css/sidebar.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    @vite(['resources/js/app.js'])
    @inertiaHead
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    @inertia
    
    <!-- Required Scripts for AdminLTE -->
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="/theme/dist/js/adminlte.min.js"></script>
</body>
</html>
