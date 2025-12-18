// js/main.js - GLOBAL JAVASCRIPT

// ============ THEME MANAGEMENT ============
function loadTheme() {
    const savedTheme = localStorage.getItem('elm-theme') || 'dark';
    const html = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');

    html.setAttribute('data-theme', savedTheme);
    if (themeIcon) {
        themeIcon.textContent = savedTheme === 'dark' ? '🌙' : '☀️';
    }
}

function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    const themeIcon = document.getElementById('themeIcon');

    html.setAttribute('data-theme', newTheme);
    if (themeIcon) {
        themeIcon.textContent = newTheme === 'dark' ? '🌙' : '☀️';
    }
    localStorage.setItem('elm-theme', newTheme);
}

// ============ SIDEBAR MOBILE TOGGLE ============
function initSidebar() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (!mobileMenuBtn || !sidebar || !sidebarOverlay) return;

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        sidebarOverlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
    }

    mobileMenuBtn.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', toggleSidebar);

    // Close sidebar when clicking menu items on mobile
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                toggleSidebar();
            }
        });
    });

    // Auto-open sidebar on desktop
    if (window.innerWidth >= 1024) {
        sidebar.classList.add('active');
    }
}

// ============ MODAL FUNCTIONS ============
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.style.display = 'flex';
    setTimeout(() => {
        modal.style.opacity = '1';
        if (modal.children[0]) {
            modal.children[0].style.transform = 'translateY(0)';
        }
    }, 10);
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.style.opacity = '0';
        if (modal.children[0]) {
            modal.children[0].style.transform = 'translateY(20px)';
        }
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    });
    document.body.style.overflow = 'auto';
}

// ============ FORM VALIDATION ============
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const requiredInputs = form.querySelectorAll('[required]');
    let isValid = true;

    requiredInputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.style.borderColor = 'var(--error)';
        } else {
            input.style.borderColor = '';
        }
    });

    return isValid;
}

// ============ INITIALIZATION ============
document.addEventListener('DOMContentLoaded', function () {
    // Load theme
    loadTheme();

    // Initialize sidebar
    initSidebar();

    // Add theme toggle event
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    // Close modal on overlay click
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // Handle window resize
    window.addEventListener('resize', function () {
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (window.innerWidth >= 1024) {
            if (sidebar) sidebar.classList.add('active');
            if (sidebarOverlay) sidebarOverlay.style.display = 'none';
        } else {
            if (sidebar) sidebar.classList.remove('active');
        }
    });
});

// ============ HELPER FUNCTIONS ============
function formatCurrency(amount) {
    return 'GHS ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}