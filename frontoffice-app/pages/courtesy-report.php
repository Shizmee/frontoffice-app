<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAuth();

// Get date range from query parameters
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validate dates
if (!validateDate($startDate) || !validateDate($endDate)) {
    $_SESSION['error'] = "Invalid date format";
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
}

// Get courtesy interactions
$stmt = $pdo->prepare("
    SELECT i.*, u.full_name as staff_name 
    FROM guest_interactions i 
    JOIN users u ON i.staff_id = u.id 
    WHERE i.type = 'courtesy' 
    AND DATE(i.interaction_date) BETWEEN ? AND ? 
    ORDER BY i.interaction_date DESC
");
$stmt->execute([$startDate, $endDate]);
$courtesies = $stmt->fetchAll();

// Group interactions by date
$groupedCourtesies = [];
foreach ($courtesies as $courtesy) {
    $date = date('Y-m-d', strtotime($courtesy['interaction_date']));
    if (!isset($groupedCourtesies[$date])) {
        $groupedCourtesies[$date] = [];
    }
    $groupedCourtesies[$date][] = $courtesy;
}
?>


<?php include '../includes/sidebar.php'; ?>

<div class="md:ml-64 p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Courtesy Report</h1>
        <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 print:hidden">
            Print Report
        </button>
    </div>

    <!-- Date Range Filter -->
    <form class="bg-white p-4 rounded-lg shadow mb-6 print:hidden">
        <div class="grid md:grid-cols-3 gap-4">
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
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Filter Report
                </button>
            </div>
        </div>
    </form>

    <!-- Report Content -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            <h2 class="text-xl font-semibold mb-2">Courtesy Report</h2>
            <p class="text-gray-600">
                Period: <?= formatDate($startDate) ?> to <?= formatDate($endDate) ?>
            </p>
            <p class="text-gray-600">
                Total Courtesies: <?= count($courtesies) ?>
            </p>
        </div>

        <?php if (empty($groupedCourtesies)): ?>
        <div class="p-6 text-gray-500 text-center border-t">
            No courtesy interactions found for the selected period.
        </div>
        <?php else: ?>
        <?php foreach ($groupedCourtesies as $date => $dayCourtesies): ?>
        <div class="border-t">
            <h3 class="bg-gray-50 px-6 py-3 font-medium">
                <?= formatDate($date) ?> 
                <span class="text-gray-500 text-sm">
                    (<?= count($dayCourtesies) ?> courtesies)
                </span>
            </h3>
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Room</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Staff</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase print:hidden">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($dayCourtesies as $courtesy): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <?= date('g:i A', strtotime($courtesy['interaction_date'])) ?>
                        </td>
                        <td class="px-6 py-4 font-medium">
                            <?= htmlspecialchars($courtesy['guest_name']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= htmlspecialchars($courtesy['room_number']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= nl2br(htmlspecialchars($courtesy['details'])) ?>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <?= htmlspecialchars($courtesy['staff_name']) ?>
                        </td>
                        <td class="px-6 py-4 print:hidden">
                            <select onchange="updateStatus(<?= $courtesy['id'] ?>, 'guest_interactions', this.value)"
                                    class="form-input py-1 px-2 text-sm">
                                <?php foreach (['pending', 'in_progress', 'resolved'] as $status): ?>
                                <option value="<?= $status ?>" <?= $courtesy['status'] === $status ? 'selected' : '' ?>>
                                    <?= ucfirst($status) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
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
