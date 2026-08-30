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
    <div style="max-width: 1280px; margin: 0 auto; padding: 16px;">
        {{ $slot }}
    </div>
    @livewireScripts
    <script>
    (() => {
        const applyTheme = () => {
            const isLight = document.body.classList.contains('light');
            const moon = document.getElementById('themeIconMoon');
            const sun = document.getElementById('themeIconSun');
            if (moon && sun) {
                moon.style.display = isLight ? 'none' : 'block';
                sun.style.display = isLight ? 'block' : 'none';
            }
        };
        const stored = localStorage.getItem('light');
        if (stored === 'true' || (stored === null && window.matchMedia('(prefers-color-scheme: light)').matches)) {
            document.body.classList.add('light');
        }
        applyTheme();
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('#themeToggle');
            if (!btn) return;
            document.body.classList.toggle('light');
            localStorage.setItem('light', document.body.classList.contains('light'));
            applyTheme();
        });
        document.addEventListener('livewire:navigated', applyTheme);
    })();
    </script>
</body>
</html>
