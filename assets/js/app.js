document.addEventListener('DOMContentLoaded', () => {
    // Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // Auto-hide flash messages after 5 seconds
    const flashMessage = document.getElementById('flashMessage');
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.transition = 'opacity 0.5s ease';
            flashMessage.style.opacity = '0';
            setTimeout(() => flashMessage.remove(), 500);
        }, 5000);
    }
});
