<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Require admin access
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $username = sanitizeInput($_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $fullName = sanitizeInput($_POST['full_name']);
                // Update role assignment logic
                $validRoles = ['admin', 'manager', 'supervisor', 'team_member'];
                $role = in_array($_POST['role'], $validRoles) ? $_POST['role'] : 'team_member';

                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $password, $fullName, $role]);

                logActivity($pdo, 'create', 'users', $pdo->lastInsertId(), "Created user: $username");
                break;

            case 'update':
                $userId = $_POST['user_id'];
                $fullName = sanitizeInput($_POST['full_name']);
                // Update role assignment logic
                $validRoles = ['admin', 'manager', 'supervisor', 'team_member'];
                $role = in_array($_POST['role'], $validRoles) ? $_POST['role'] : 'team_member';

                $sql = "UPDATE users SET full_name = ?, role = ? WHERE id = ?";
                $params = [$fullName, $role, $userId];

                if (!empty($_POST['password'])) {
                    $sql = "UPDATE users SET full_name = ?, role = ?, password = ? WHERE id = ?";
                    $params = [$fullName, $role, password_hash($_POST['password'], PASSWORD_DEFAULT), $userId];
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                logActivity($pdo, 'update', 'users', $userId, "Updated user details");
                break;

            case 'delete':
                if (!isAdmin() && !isManager()) {
                    $_SESSION['error'] = 'You do not have permission to delete users.';
                    header('Location: users.php');
                    exit();
                }
                $userId = $_POST['user_id'];
                
                // Don't allow deleting the last admin
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                $adminCount = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $userRole = $stmt->fetchColumn();

                if ($userRole === 'admin' && $adminCount <= 1) {
                    $_SESSION['error'] = "Cannot delete the last admin user.";
                    break;
                }

                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);

                logActivity($pdo, 'delete', 'users', $userId, "Deleted user");
                break;
        }

        header('Location: users.php');
        exit();
    }
}
?>
<!-- Removed gradient and extra overlays to show background image -->

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="p-8 bg-gray-900 bg-opacity-70 rounded-lg shadow-lg w-full min-h-screen mx-auto transition-colors duration-200 overflow-x-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">User Management</h1>
        <?php
        $currentUserRole = $_SESSION['role'] ?? '';
        $canCreate = isAdmin();
        $canEdit = isAdmin() || isSupervisor() || isTeamMember();
        $canDelete = isAdmin() || isManager();
        ?>
        <?php if ($canCreate): ?>
            <button onclick="document.getElementById('createUserModal').classList.remove('hidden')" 
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Add New User
            </button>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
                while ($user = $stmt->fetch()):
                ?>
                <?php
                $currentUserRole = $_SESSION['role'] ?? '';
                $canEdit = isAdmin() || isSupervisor() || isTeamMember();
                $canDelete = isAdmin() || isManager();
                ?>
                <tr>
                    <td class="px-6 py-4"><?= htmlspecialchars($user['username']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($user['full_name']) ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4"><?= formatDate($user['created_at']) ?></td>
                    <td class="px-6 py-4">
                        <?php if ($canEdit): ?>
                            <button onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)"
                                    class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                        <?php endif; ?>
                        <?php if ($user['id'] !== $_SESSION['user_id'] && $canDelete): ?>
                            <button onclick="deleteUser(<?= $user['id'] ?>)"
                                    class="text-red-600 hover:text-red-900">Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($canCreate): ?>
    <!-- Create User Modal -->
    <div id="createUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Create New User</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-4">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-input" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-input" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-input" id="createUserRole">
                            <option value="team_member" title="Can edit users">Team Member</option>
                            <option value="supervisor" title="Can edit users">Supervisor</option>
                            <option value="manager" title="Can delete users">Manager</option>
                            <?php if (isAdmin()): ?>
                            <option value="admin" title="Full rights (edit, delete, create)">Admin</option>
                            <?php endif; ?>
                        </select>
                        <small class="text-gray-500 block mt-1">
                            <b>Team Member</b> &amp; <b>Supervisor</b>: Can edit users.<br>
                            <b>Manager</b>: Can delete users.<br>
                            <b>Admin</b>: Full rights (edit, delete, create). Only Admins can assign this role.
                        </small>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModals()"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Edit User</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="mb-4">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="editFullName" class="form-input" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-input">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Role</label>
                        <select name="role" id="editRole" class="form-input">
                            <option value="team_member">Team Member</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModals()"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Form (Hidden) -->
    <form id="deleteUserForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>
</div>

<script>
function closeModals() {
    document.getElementById('createUserModal').classList.add('hidden');
    document.getElementById('editUserModal').classList.add('hidden');
}

function editUser(user) {
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editFullName').value = user.full_name;
    document.getElementById('editRole').value = user.role;
    document.getElementById('editUserModal').classList.remove('hidden');
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteUserForm').submit();
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = document.getElementsByClassName('fixed inset-0');
    for (let modal of modals) {
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    }
}
</script>

<?php include '../includes/footer.php'; ?>
