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

    const initTheme = () => {
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
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }

    document.addEventListener('livewire:navigated', applyTheme);
})();
