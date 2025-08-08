<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'guest_name' => sanitizeInput($_POST['guest_name']),
        'room_number' => sanitizeInput($_POST['room_number']),
        'num_guests' => intval($_POST['num_guests']),
        'reservation_date' => $_POST['reservation_date'],
        'reservation_time' => $_POST['reservation_time'],
        'special_requests' => sanitizeInput($_POST['special_requests']),
        'staff_id' => $_SESSION['user_id']
    ];

    if (!validateDate($data['reservation_date'])) {
        $_SESSION['error'] = "Invalid date format";
        header('Location: dinner-reservation.php');
        exit();
    }

    $sql = "INSERT INTO dinner_reservations (guest_name, room_number, num_guests, reservation_date, 
            reservation_time, special_requests, staff_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    
    $reservationId = $pdo->lastInsertId();
    logActivity($pdo, 'create', 'dinner_reservations', $reservationId, 
                "Created dinner reservation for {$data['guest_name']}");
    
    header('Location: dinner-reservation.php');
    exit();
}

// Get today's and future reservations
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as staff_name 
    FROM dinner_reservations r 
    JOIN users u ON r.staff_id = u.id 
    WHERE r.reservation_date >= CURDATE() 
    ORDER BY r.reservation_date ASC, r.reservation_time ASC
");
$stmt->execute();
$reservations = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="md:ml-64 p-6">
    <h1 class="text-2xl font-bold mb-6">Dinner Reservations</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Reservation Form -->
    <form method="POST" class="bg-white p-6 rounded-lg shadow mb-6">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Guest Name</label>
                <input type="text" name="guest_name" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Room Number</label>
                <input type="text" name="room_number" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Number of Guests</label>
                <input type="number" name="num_guests" min="1" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Date</label>
                <input type="date" name="reservation_date" class="form-input" required
                       min="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="form-label">Time</label>
                <select name="reservation_time" class="form-input" required>
                    <?php
                    $start = strtotime('18:00');
                    $end = strtotime('22:00');
                    for ($time = $start; $time <= $end; $time += 1800) { // 30-minute intervals
                        printf(
                            '<option value="%s">%s</option>',
                            date('H:i:s', $time),
                            date('g:i A', $time)
                        );
                    }
                    ?>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Special Requests</label>
                <textarea name="special_requests" rows="2" class="form-input"></textarea>
            </div>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded mt-4 hover:bg-blue-700">
            Make Reservation
        </button>
    </form>

    <!-- Reservations List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($reservations as $reservation): ?>
                <tr>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">
                            <?= htmlspecialchars($reservation['guest_name']) ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            Room <?= htmlspecialchars($reservation['room_number']) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div><?= formatDate($reservation['reservation_date']) ?></div>
                        <div class="text-sm text-gray-500">
                            <?= formatTime($reservation['reservation_time']) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div><?= $reservation['num_guests'] ?> guests</div>
                        <?php if ($reservation['special_requests']): ?>
                        <div class="text-sm text-gray-500">
                            <?= htmlspecialchars($reservation['special_requests']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <select onchange="updateStatus(<?= $reservation['id'] ?>, 'dinner_reservations', this.value)"
                                class="form-input py-1 px-2 text-sm">
                            <?php foreach (['confirmed', 'seated', 'completed', 'cancelled'] as $status): ?>
                            <option value="<?= $status ?>" <?= $reservation['status'] === $status ? 'selected' : '' ?>>
                                <?= ucfirst($status) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="px-6 py-4">
                        <button type="button" onclick="showDetails(<?= htmlspecialchars(json_encode($reservation)) ?>)"
                                class="text-blue-600 hover:text-blue-900">
                            Details
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Reservation Details</h3>
            <div id="modalContent" class="space-y-4">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(reservation) {
    const content = document.getElementById('modalContent');
    content.innerHTML = `
        <p><strong>Guest:</strong> ${reservation.guest_name}</p>
        <p><strong>Room:</strong> ${reservation.room_number}</p>
        <p><strong>Number of Guests:</strong> ${reservation.num_guests}</p>
        <p><strong>Date:</strong> ${formatDate(reservation.reservation_date)}</p>
        <p><strong>Time:</strong> ${formatTime(reservation.reservation_time)}</p>
        <p><strong>Special Requests:</strong> ${reservation.special_requests || 'None'}</p>
        <p><strong>Reserved by:</strong> ${reservation.staff_name}</p>
        <p><strong>Status:</strong> ${reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1)}</p>
    `;
    document.getElementById('detailsModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const date = new Date();
    date.setHours(hours);
    date.setMinutes(minutes);
    return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit', 
        hour12: true 
    });
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('detailsModal');
    if (event.target === modal) {
        modal.classList.add('hidden');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
