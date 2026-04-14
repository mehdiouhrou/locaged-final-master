document.addEventListener('DOMContentLoaded', function () {
    initializeSidebar();
});

function initializeSidebar() {
    const mainContent = document.querySelector('.main-content');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebar) {
        sidebar.classList.remove('collapsed');
    }
    if (mainContent) {
        mainContent.classList.remove('collapsed');
    }

    // Accordéon : sous-menus dans la barre (sidebar reste toujours étendue).
    document.querySelectorAll('.sidebar-menu a').forEach((link) => {
        link.addEventListener('click', function (e) {
            if (!this.parentElement.classList.contains('has-submenu')) {
                return;
            }
            e.preventDefault();
            const li = this.parentElement;
            li.classList.toggle('active');
            overlay && overlay.classList.remove('show');
        });
    });

    if (overlay) {
        overlay.addEventListener('click', () => {
            overlay.classList.remove('show');
        });
    }
}
