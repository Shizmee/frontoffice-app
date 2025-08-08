<!-- Change Password Modal -->
<div id="changePasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800 dark:border-gray-700">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">Change Password</h3>
            <form id="changePasswordForm" method="POST" action="/frontoffice-app/auth/change-password.php">
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Username</label>
                    <input type="text" name="username" value="<?= $_SESSION['username'] ?>" 
                           class="w-full p-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" readonly>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Current Password</label>
                    <input type="password" name="current_password" 
                           class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">New Password</label>
                    <input type="password" name="new_password" 
                           class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2">Confirm New Password</label>
                    <input type="password" name="confirm_password" 
                           class="w-full p-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required>
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
function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

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

document.getElementById('menuToggle')?.addEventListener('click', toggleMenu);

// Add password form validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPass = this.elements['new_password'].value;
    const confirmPass = this.elements['confirm_password'].value;
    
    if (newPass !== confirmPass) {
        e.preventDefault();
        alert('New password and confirmation do not match!');
        return false;
    }
    
    if (newPass.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
});
</script>
</body>
</html>