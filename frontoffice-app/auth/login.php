<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        logActivity($pdo, 'login', 'auth', null, "User {$user['username']} logged in");

        header('Location: ../pages/dashboard.php');
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - FO System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
        // Check for saved dark mode preference
        if (localStorage.getItem('darkMode') === 'true' || 
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="flex items-center justify-center h-screen relative overflow-hidden">
    <!-- Video Background -->
    <div class="fixed inset-0 w-full h-full">
        <video class="w-full h-full object-cover" autoplay muted loop playsinline>
            <source src="/frontoffice-app/assets/img/canareef.mp4" type="video/mp4">
        </video>
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    </div>
    <div class="relative z-10 bg-white/30 dark:bg-gray-800/30 backdrop-blur-xl p-8 rounded-lg shadow-lg w-full max-w-md border border-white/30 hover:bg-white/40 dark:hover:bg-gray-800/40 transition-all duration-300">
        <!-- Glass reflection effect -->
        <div class="absolute inset-0 bg-gradient-to-br from-white/50 to-transparent dark:from-white/10 rounded-lg pointer-events-none"></div>
        <div class="relative">
            <div class="flex justify-center mb-10">
            <img src="/frontoffice-app/assets/img/logo.png" alt="Logo" class="h-32 w-auto">
        </div>
        <h2 class="text-2xl font-bold text-center text-gray-800 dark:text-gray-100 mb-6">Front Office Login</h2>
        <?php if (isset($_SESSION['success'])): ?>
            <p class="text-green-500 dark:text-green-400 text-sm mb-4"><?= htmlspecialchars($_SESSION['success']) ?></p>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="text-red-500 dark:text-red-400 text-sm mb-4"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="text-red-500 dark:text-red-400 text-sm mb-4"><?= htmlspecialchars($_SESSION['error']) ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" class="w-full bg-white/20 dark:bg-gray-900/20 backdrop-blur-md border border-white/30 dark:border-gray-600/30 text-gray-900 dark:text-gray-100 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#876432] placeholder-gray-500 dark:placeholder-gray-400" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Password</label>
                <input type="password" name="password" class="w-full bg-white/20 dark:bg-gray-900/20 backdrop-blur-md border border-white/30 dark:border-gray-600/30 text-gray-900 dark:text-gray-100 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#876432] placeholder-gray-500 dark:placeholder-gray-400" required>
            </div>
            <div class="flex justify-between items-center mb-4">
                <button type="button" id="darkModeToggle" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    <!-- Sun icon -->
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <!-- Moon icon -->
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>
                <button type="button" onclick="showChangePasswordModal()" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm">
                    Change Password
                </button>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition">Login</button>
        </form>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50">
        <div class="relative top-20 mx-auto p-5 w-96 shadow-lg rounded-lg bg-white/30 dark:bg-gray-800/30 backdrop-blur-xl border border-white/30 hover:bg-white/40 dark:hover:bg-gray-800/40 transition-all duration-300">
            <!-- Glass reflection effect -->
            <div class="absolute inset-0 bg-gradient-to-br from-white/50 to-transparent dark:from-white/10 rounded-lg pointer-events-none"></div>
            <div class="relative">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">Change Password</h3>
                <form id="changePasswordForm" method="POST" action="change-password.php">
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Username</label>
                        <input type="text" name="username" 
                               class="w-full bg-white/20 dark:bg-gray-900/20 backdrop-blur-md border border-white/30 dark:border-gray-600/30 text-gray-900 dark:text-gray-100 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#876432] placeholder-gray-500 dark:placeholder-gray-400" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Current Password</label>
                        <input type="password" name="current_password" 
                               class="w-full bg-white/20 dark:bg-gray-900/20 backdrop-blur-md border border-white/30 dark:border-gray-600/30 text-gray-900 dark:text-gray-100 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#876432] placeholder-gray-500 dark:placeholder-gray-400" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">New Password</label>
                        <input type="password" name="new_password" 
                               class="w-full bg-white/20 dark:bg-gray-900/20 backdrop-blur-md border border-white/30 dark:border-gray-600/30 text-gray-900 dark:text-gray-100 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-[#876432] placeholder-gray-500 dark:placeholder-gray-400" required>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="hideChangePasswordModal()"
                                class="px-4 py-2 bg-gray-300 text-gray-700 dark:bg-gray-600 dark:text-gray-200 rounded hover:bg-gray-400 dark:hover:bg-gray-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function showChangePasswordModal() {
        document.getElementById('changePasswordModal').classList.remove('hidden');
    }

    function hideChangePasswordModal() {
        document.getElementById('changePasswordModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('changePasswordModal');
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    }

    // Dark mode toggle functionality
    const darkModeToggle = document.getElementById('darkModeToggle');
    darkModeToggle.addEventListener('click', () => {
        const isDarkMode = document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', isDarkMode);
    });

    // Add password form validation
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const username = this.elements['username'].value;
        const currentPass = this.elements['current_password'].value;
        const newPass = this.elements['new_password'].value;
        
        if (!username || !currentPass || !newPass) {
            alert('All fields are required!');
            return;
        }
        
        if (newPass.length < 6) {
            alert('New password must be at least 6 characters long!');
            return;
        }

        this.submit();
    });
    </script>
</body>
</html>