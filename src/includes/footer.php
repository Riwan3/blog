<script type="text/javascript" src="/assets/scripts/main.js"></script>
<script>
// Dark Mode Toggle - Inline to ensure it works
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const darkModeIcon = document.getElementById('darkModeIcon');

    if (!darkModeToggle || !darkModeIcon) {
        console.log('Dark mode elements not found');
        return;
    }

    // Check saved preference
    const darkMode = localStorage.getItem('adminDarkMode');

    if (darkMode === 'enabled') {
        enableDarkMode();
    }

    // Toggle on button click
    darkModeToggle.addEventListener('click', function(e) {
        e.preventDefault();
        const isDarkMode = document.body.classList.contains('dark-mode');

        if (isDarkMode) {
            disableDarkMode();
        } else {
            enableDarkMode();
        }
    });

    function enableDarkMode() {
        document.body.classList.add('dark-mode');
        darkModeIcon.classList.remove('pe-7s-moon');
        darkModeIcon.classList.add('pe-7s-sun');
        localStorage.setItem('adminDarkMode', 'enabled');
        console.log('Dark mode enabled');
    }

    function disableDarkMode() {
        document.body.classList.remove('dark-mode');
        darkModeIcon.classList.remove('pe-7s-sun');
        darkModeIcon.classList.add('pe-7s-moon');
        localStorage.setItem('adminDarkMode', 'disabled');
        console.log('Dark mode disabled');
    }
});
</script>
