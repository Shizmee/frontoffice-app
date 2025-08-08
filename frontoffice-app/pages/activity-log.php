<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAuth();

// Get filters from query parameters
$module = isset($_GET['module']) ? $_GET['module'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$userId = isset($_GET['user_id']) ? $_GET['user_id'] : '';

// Validate dates
if (!validateDate($startDate) || !validateDate($endDate)) {
    $_SESSION['error'] = "Invalid date format";
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
}

// Build query
$sql = "SELECT l.*, u.full_name as user_name 
        FROM activity_logs l 
        JOIN users u ON l.user_id = u.id 
        WHERE DATE(l.created_at) BETWEEN :start_date AND :end_date";

$params = [
    ':start_date' => $startDate,
    ':end_date' => $endDate
];

if ($module) {
    $sql .= " AND l.module = :module";
    $params[':module'] = $module;
}

if ($action) {
    $sql .= " AND l.action = :action";
    $params[':action'] = $action;
}

if ($userId) {
    $sql .= " AND l.user_id = :user_id";
    $params[':user_id'] = $userId;
}

$sql .= " ORDER BY l.created_at DESC";

// Get logs
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get users for filter
$stmt = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name");
$users = $stmt->fetchAll();

// Get unique modules and actions for filters
$stmt = $pdo->query("SELECT DISTINCT module FROM activity_logs ORDER BY module");
$modules = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");
$actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="md:ml-64 p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Activity Log</h1>
        <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 print:hidden">
            Print Log
        </button>
    </div>

    <!-- Filters -->
    <form class="bg-white p-4 rounded-lg shadow mb-6 print:hidden">
        <div class="grid md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div>
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" value="<?= $startDate ?>" 
                       class="form-input" max="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" value="<?= $endDate ?>" 
                       class="form-input" max="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="form-label">Module</label>
                <select name="module" class="form-input">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $mod): ?>
                    <option value="<?= $mod ?>" <?= $module === $mod ? 'selected' : '' ?>>
                        <?= ucfirst($mod) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Action</label>
                <select name="action" class="form-input">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $act): ?>
                    <option value="<?= $act ?>" <?= $action === $act ? 'selected' : '' ?>>
                        <?= ucfirst($act) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">User</label>
                <select name="user_id" class="form-input">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?= $user['id'] ?>" <?= $userId == $user['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Apply Filters
                </button>
            </div>
        </div>
    </form>

    <!-- Activity Log -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold mb-2">Activity Log</h2>
            <p class="text-gray-600">
                Period: <?= formatDate($startDate) ?> to <?= formatDate($endDate) ?>
            </p>
            <p class="text-gray-600">
                Total Activities: <?= count($logs) ?>
            </p>
        </div>

        <?php if (empty($logs)): ?>
        <div class="p-6 text-gray-500 text-center border-t">
            No activities found for the selected filters.
        </div>
        <?php else: ?>
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Timestamp</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Module</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="px-6 py-4 text-sm">
                        <?= formatDateTime($log['created_at']) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($log['user_name']) ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded 
                            <?php
                            switch ($log['module']) {
                                case 'auth': echo 'bg-purple-100 text-purple-800'; break;
                                case 'guest_calls': echo 'bg-blue-100 text-blue-800'; break;
                                case 'guest_interactions': echo 'bg-green-100 text-green-800'; break;
                                case 'excursions': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'dinner_reservations': echo 'bg-pink-100 text-pink-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?= ucfirst($log['module']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded
                            <?php
                            switch ($log['action']) {
                                case 'create': echo 'bg-green-100 text-green-800'; break;
                                case 'update': echo 'bg-blue-100 text-blue-800'; break;
                                case 'delete': echo 'bg-red-100 text-red-800'; break;
                                case 'login': echo 'bg-purple-100 text-purple-800'; break;
                                case 'logout': echo 'bg-gray-100 text-gray-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?= ucfirst($log['action']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($log['description']) ?>
                        <?php if ($log['record_id']): ?>
                        <span class="text-sm text-gray-500">
                            (ID: <?= $log['record_id'] ?>)
                        </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<style type="text/css" media="print">
    @page { size: landscape; }
    .print\\:hidden { display: none !important; }
    body { padding: 20px; }
    .md\\:ml-64 { margin-left: 0 !important; }
</style>

<?php include '../includes/footer.php'; ?>
