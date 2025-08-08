<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'excursion_name' => sanitizeInput($_POST['excursion_name']),
        'description' => sanitizeInput($_POST['description']),
        'price' => floatval($_POST['price']),
        'guest_name' => sanitizeInput($_POST['guest_name']),
        'room_number' => sanitizeInput($_POST['room_number']),
        'num_guests' => intval($_POST['num_guests']),
        'excursion_date' => $_POST['excursion_date'],
        'staff_id' => $_SESSION['user_id']
    ];

    if (!validateDate($data['excursion_date'])) {
        $_SESSION['error'] = "Invalid date format";
        header('Location: excursion-booking.php');
        exit();
    }

    $sql = "INSERT INTO excursions (excursion_name, description, price, guest_name, room_number, num_guests, excursion_date, staff_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    
    $bookingId = $pdo->lastInsertId();
    logActivity($pdo, 'create', 'excursions', $bookingId, "Booked excursion: {$data['excursion_name']}");
    
    header('Location: excursion-booking.php');
    exit();
}

// Get list of excursions for the current and future dates
$stmt = $pdo->prepare("
    SELECT e.*, u.full_name as staff_name 
    FROM excursions e 
    JOIN users u ON e.staff_id = u.id 
    WHERE e.excursion_date >= CURDATE() 
    ORDER BY e.excursion_date ASC, e.created_at DESC
");
$stmt->execute();
$excursions = $stmt->fetchAll();
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="md:ml-64 p-6">
    <h1 class="text-2xl font-bold mb-6">Excursion Booking</h1>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Booking Form -->
    <form method="POST" class="bg-white p-6 rounded-lg shadow mb-6">
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="form-label">Excursion Name</label>
                <input type="text" name="excursion_name" class="form-input" required>
            </div>
            <div>
                <label class="form-label">Price</label>
                <input type="number" name="price" step="0.01" class="form-input" required>
            </div>
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
                <label class="form-label">Excursion Date</label>
                <input type="date" name="excursion_date" class="form-input" required
                       min="<?= date('Y-m-d') ?>">
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-input"></textarea>
            </div>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded mt-4 hover:bg-blue-700">
            Book Excursion
        </button>
    </form>

    <!-- Excursions List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Excursion</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guest</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($excursions as $excursion): ?>
                <tr>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900"><?= htmlspecialchars($excursion['excursion_name']) ?></div>
                        <div class="text-sm text-gray-500">
                            <?= $excursion['num_guests'] ?> guests | Room <?= htmlspecialchars($excursion['room_number']) ?>
                        </div>
                    </td>
                    <td class="px-6 py-4"><?= htmlspecialchars($excursion['guest_name']) ?></td>
                    <td class="px-6 py-4"><?= formatDate($excursion['excursion_date']) ?></td>
                    <td class="px-6 py-4"><?= formatCurrency($excursion['price']) ?></td>
                    <td class="px-6 py-4">
                        <select onchange="updateStatus(<?= $excursion['id'] ?>, 'excursions', this.value)"
                                class="form-input py-1 px-2 text-sm">
                            <?php foreach (['booked', 'confirmed', 'completed', 'cancelled'] as $status): ?>
                            <option value="<?= $status ?>" <?= $excursion['status'] === $status ? 'selected' : '' ?>>
                                <?= ucfirst($status) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td class="px-6 py-4">
                        <button type="button" onclick="showDetails(<?= htmlspecialchars(json_encode($excursion)) ?>)"
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
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Excursion Details</h3>
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
function showDetails(excursion) {
    const content = document.getElementById('modalContent');
    content.innerHTML = `
        <p><strong>Excursion:</strong> ${excursion.excursion_name}</p>
        <p><strong>Description:</strong> ${excursion.description || 'N/A'}</p>
        <p><strong>Guest:</strong> ${excursion.guest_name}</p>
        <p><strong>Room:</strong> ${excursion.room_number}</p>
        <p><strong>Guests:</strong> ${excursion.num_guests}</p>
        <p><strong>Date:</strong> ${formatDate(excursion.excursion_date)}</p>
        <p><strong>Price:</strong> ${formatCurrency(excursion.price)}</p>
        <p><strong>Booked by:</strong> ${excursion.staff_name}</p>
        <p><strong>Status:</strong> ${excursion.status.charAt(0).toUpperCase() + excursion.status.slice(1)}</p>
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

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
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
