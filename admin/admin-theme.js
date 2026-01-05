/**
 * Rockband Scheduler - Admin Theme Management
 * Handles dark/light mode switching with localStorage persistence
 * Default: light on desktop, dark on mobile
 */

(function() {
    'use strict';

    const THEME_KEY = 'adminTheme';
    const MOBILE_BREAKPOINT = 992; // Bootstrap's lg breakpoint

    /**
     * Get the default theme based on screen size
     * Mobile (< 992px): dark
     * Desktop (>= 992px): light
     */
    function getDefaultTheme() {
        return window.innerWidth < MOBILE_BREAKPOINT ? 'dark' : 'light';
    }

    /**
     * Get the stored theme or default
     */
    function getStoredTheme() {
        return localStorage.getItem(THEME_KEY);
    }

    /**
     * Get the effective theme (stored or default)
     */
    function getEffectiveTheme() {
        return getStoredTheme() || getDefaultTheme();
    }

    /**
     * Apply theme to the document
     */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        updateToggleIcon(theme);
    }

    /**
     * Update the toggle button icon
     */
    function updateToggleIcon(theme) {
        const icon = document.getElementById('themeToggleIcon');
        if (icon) {
            icon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }
    }

    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') || getEffectiveTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem(THEME_KEY, newTheme);
        applyTheme(newTheme);
    }

    /**
     * Reset to default (remove stored preference)
     */
    function resetToDefault() {
        localStorage.removeItem(THEME_KEY);
        applyTheme(getDefaultTheme());
    }

    // Apply theme immediately to prevent flash
    applyTheme(getEffectiveTheme());

    // Expose toggle function globally
    window.toggleAdminTheme = toggleTheme;
    window.resetAdminTheme = resetToDefault;

    // Update icon once DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            updateToggleIcon(getEffectiveTheme());
        });
    } else {
        updateToggleIcon(getEffectiveTheme());
    }
})();
