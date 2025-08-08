<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'guest_name' => $_POST['guest_name'],
        'room_number' => $_POST['room_number'],
        'call_type' => $_POST['call_type'],
        'details' => $_POST['details'],
        'assigned_staff_id' => $_SESSION['user_id'],
        'created_by' => $_SESSION['user_id']
    ];

    $sql = "INSERT INTO guest_calls (guest_name, room_number, call_type, details, assigned_staff_id, created_by) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));

    logActivity($pdo, 'create', 'guest_calls', $pdo->lastInsertId(), "Call from {$data['guest_name']}");
    header('Location: guest-calls.php');
    exit();
}
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="md:ml-64 p-6">
    <h1 class="text-2xl font-bold mb-6">Guest Call Log</h1>

    <!-- Form -->
    <form method="POST" class="bg-white p-6 rounded-lg shadow mb-6 grid md:grid-cols-2 gap-4">
        <div>
            <label class="block mb-1">Guest Name</label>
            <input type="text" name="guest_name" class="w-full border rounded p-2" required>
        </div>
        <div>
            <label class="block mb-1">Room Number</label>
            <input type="text" name="room_number" class="w-full border rounded p-2" required>
        </div>
        <div>
            <label class="block mb-1">Call Type</label>
            <select name="call_type" class="w-full border rounded p-2" required>
                <option value="request">Request</option>
                <option value="complaint">Complaint</option>
                <option value="information">Information</option>
            </select>
        </div>
        <div>
            <label class="block mb-1">Details</label>
            <textarea name="details" class="w-full border rounded p-2" rows="2" required></textarea>
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Log Call</button>
        </div>
    </form>

    <!-- Table -->
    <table class="w-full bg-white rounded shadow overflow-hidden">
        <thead class="bg-gray-100">
            <tr>
                <th class="p-2 text-left">Guest</th>
                <th class="p-2 text-left">Room</th>
                <th class="p-2 text-left">Type</th>
                <th class="p-2 text-left">Status</th>
                <th class="p-2 text-left">Time</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->query("SELECT * FROM guest_calls ORDER BY created_at DESC");
            while ($row = $stmt->fetch()):
            ?>
            <tr class="border-t">
                <td class="p-2"><?= htmlspecialchars($row['guest_name']) ?></td>
                <td class="p-2"><?= htmlspecialchars($row['room_number']) ?></td>
                <td class="p-2"><?= ucfirst($row['call_type']) ?></td>
                <td class="p-2">
                    <span class="px-2 py-1 text-xs rounded <?= $row['status'] == 'closed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                        <?= ucfirst($row['status']) ?>
                    </span>
                </td>
                <td class="p-2"><?= date('M d, H:i', strtotime($row['created_at'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>