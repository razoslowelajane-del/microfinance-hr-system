document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // First fetch
    loadDashboardStats();

    // Auto-update every 1 minute
    setInterval(loadDashboardStats, 60000);

    // Theme Toggle persistence
    const themeBtn = document.getElementById('theme-toggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            document.cookie = "theme=" + (isDark ? "dark" : "light") + ";path=/";
        });
    }
});

async function loadDashboardStats() {
    try {
        const response = await fetch('includes/dashboard_data.php');
        const result = await response.json();

        if (result.status === 'success') {
            const d = result.data;
            
            // UI Mappings - update IDs
            updateElement('roster-count', d.rosters);
            updateElement('leave-count', d.leaves);
            updateElement('claim-count', d.claims);
            updateElement('flag-count', d.flags);
            
        } else {
            console.error('API Error:', result.message);
        }
    } catch (error) {
        console.error('Connection Error:', error);
    }
}

function updateElement(id, value) {
    const el = document.getElementById(id);
    if (el) {
        // Counter animation
        const startValue = parseInt(el.innerText) || 0;
        animateCount(el, startValue, value, 800);
    }
}

function animateCount(obj, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        obj.innerHTML = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}