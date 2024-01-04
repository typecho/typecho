/*!
 * Minimal theme switcher
 */

const themeSwitcher = {
    init: function() {
        const setTheme = () => {
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        };

        // Set theme on page load
        setTheme();

        // Update theme if system color scheme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', setTheme);
    },
};

// Init
themeSwitcher.init();