/**
 * Global Loading Indicator
 * Duta Damai Kalimantan Selatan - Blog System
 */

// Create loading overlay element
const loadingHTML = `
    <div class="global-loading-overlay" id="globalLoadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading...</div>
        </div>
    </div>
`;

// Inject loading overlay to body
document.addEventListener('DOMContentLoaded', function() {
    document.body.insertAdjacentHTML('afterbegin', loadingHTML);

    const overlay = document.getElementById('globalLoadingOverlay');

    // Set initial state as hidden
    if (overlay) {
        overlay.classList.add('hidden');
        overlay.style.display = 'none';
    }

    // Hide loading when page is fully loaded
    window.addEventListener('load', function() {
        hideLoading();
    });

    // Show loading for all navigation links
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');

            // Skip for:
            // - javascript: links
            // - # anchors
            // - external links
            // - links with onclick that returns false
            // - download links
            if (!href ||
                href === '#' ||
                href.startsWith('javascript:') ||
                href.startsWith('mailto:') ||
                href.startsWith('tel:') ||
                this.target === '_blank' ||
                this.hasAttribute('download')) {
                return;
            }

            // Don't show for delete confirmations
            if (this.onclick && this.onclick.toString().indexOf('confirm') !== -1) {
                // Only show if user confirms
                const originalOnclick = this.onclick;
                this.onclick = function(e) {
                    const result = originalOnclick.call(this, e);
                    if (result !== false) {
                        showLoading();
                    }
                    return result;
                };
                return;
            }

            showLoading();
        });
    });

    // Show loading for all form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            // Skip for forms with custom validation that might cancel
            if (!this.checkValidity || this.checkValidity()) {
                showLoading();
            }
        });
    });

    // Show loading for browser back/forward buttons
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            hideLoading();
        }
    });

    // Hide loading if user stops navigation (ESC key)
    window.addEventListener('beforeunload', function() {
        showLoading();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideLoading();
        }
    });
});

function showLoading() {
    const overlay = document.getElementById('globalLoadingOverlay');
    if (overlay) {
        overlay.classList.remove('hidden');
        overlay.style.display = 'flex';
        overlay.style.pointerEvents = 'auto';

        // Auto hide after 2 seconds as safety measure
        setTimeout(() => {
            hideLoading();
        }, 2000);
    }
}

function hideLoading() {
    const overlay = document.getElementById('globalLoadingOverlay');
    if (overlay) {
        overlay.classList.add('hidden');
        overlay.style.display = 'none';
        overlay.style.pointerEvents = 'none';
    }
}
