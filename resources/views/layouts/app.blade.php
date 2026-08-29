<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GitPulse') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@100;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased">
    <livewire:topbar />
    <div class="flex" style="max-width: 1280px; margin: 0 auto; gap: 24px; padding: 16px;">
        <aside class="hidden lg:block" style="width: 260px; flex-shrink: 0; margin-top: 8px;">
            <livewire:repo-sidebar />
        </aside>
        <main class="flex-1 min-w-0">
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
