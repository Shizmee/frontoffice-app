<button id="sidebarToggle" class="fixed top-4 left-4 z-40 p-2 rounded-lg bg-white dark:bg-gray-800 shadow-lg border dark:border-gray-700">
    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
</button>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-30 bg-white dark:bg-gray-800 w-64 shadow-lg transform -translate-x-full transition-transform duration-300 ease-in-out border-r dark:border-gray-700">
    <div class="flex justify-between items-center p-4 border-b dark:border-gray-700">
        <div>
            <h2 class="text-xl font-bold text-blue-700 dark:text-blue-400">Front Office</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">User: <?= isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Guest' ?></p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Role: <?= isset($_SESSION['role']) ? ucfirst(htmlspecialchars($_SESSION['role'])) : 'N/A' ?></p>
        </div>
    </div>
    <nav class="mt-4">
        <a href="/frontoffice-app/pages/dashboard.php" class="block px-4 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 border-l-4 border-blue-500 font-medium dark:text-gray-100">Dashboard</a>
        <a href="/frontoffice-app/pages/guest-interactions.php" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-100">Guest Comments & Courtesy</a>
        <a href="/frontoffice-app/pages/buggy-log.php" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-100">Buggy Log</a>
        <div class="mt-4 mb-2 px-4 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Reports</div>
        <div class="ml-2">
            <a href="/frontoffice-app/pages/overview-report.php" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-100">Overview Report</a>
            <a href="/frontoffice-app/pages/buggy-log-report.php" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-100">Buggy Log Report</a>
            <a href="/frontoffice-app/pages/guest-comments-report.php" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-100">Guest Comments Report</a>
            <a href="/frontoffice-app/pages/courtesy-report-report.php" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-100">Courtesy Report</a>
        </div>
        <?php if (isAdmin()): ?>
        <a href="/frontoffice-app/pages/users.php" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-100">User Management</a>
        <?php endif; ?>
        <a href="/frontoffice-app/auth/logout.php" class="block px-4 py-3 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">Logout</a>
    </nav>
</aside>

<!-- Overlay -->
<div id="overlay" class="fixed inset-0 bg-black opacity-50 hidden z-20" onclick="toggleSidebar()"></div>

<script>
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('overlay');
    const mainContent = document.querySelector('.md\\:ml-64');
    let isSidebarVisible = false;

    function toggleSidebar() {
        isSidebarVisible = !isSidebarVisible;
        if (isSidebarVisible) {
            sidebar.classList.remove('-translate-x-full');
            mainContent.classList.add('ml-64');
            mainContent.classList.remove('ml-0');
            sidebarToggle.classList.add('left-[260px]');
            sidebarToggle.classList.remove('left-4');
            // Rotate menu icon to close (×)
            sidebarToggle.innerHTML = `
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            `;
        } else {
            sidebar.classList.add('-translate-x-full');
            mainContent.classList.remove('ml-64');
            mainContent.classList.add('ml-0');
            sidebarToggle.classList.remove('left-[260px]');
            sidebarToggle.classList.add('left-4');
            // Change back to menu icon (≡)
            sidebarToggle.innerHTML = `
                <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            `;
        }
        overlay.classList.toggle('hidden');
    }

    sidebarToggle.addEventListener('click', toggleSidebar);

    // Close sidebar when clicking outside
    document.addEventListener('click', (e) => {
        if (isSidebarVisible && 
            !sidebar.contains(e.target) && 
            !sidebarToggle.contains(e.target)) {
            toggleSidebar();
        }
    });
</script>