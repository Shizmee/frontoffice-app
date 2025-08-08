<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Initialize variables
$success_message = '';
$error_message = '';

// Generate a new form token if it doesn't exist
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// Get the selected date or default to today
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
// Validate the date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Fetch entries for the selected date
$stmt = $pdo->prepare("SELECT * FROM call_log WHERE DATE(entry_date) = ? ORDER BY time DESC");
$stmt->execute([$selectedDate]);
$result = $stmt;

// Get messages from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Fetch checked-in rooms and guest names
$roomsQuery = "SELECT Room_No, Guest_Name FROM fo_bob 
             WHERE Arrival_Date <= ? 
             AND Departure_Date >= ? 
             AND UPPER(Resv_Status) = 'CHECKED IN' 
             ORDER BY Room_No";

$roomsStmt = $pdo->prepare($roomsQuery);
$roomsStmt->execute([$selectedDate, $selectedDate]);
$rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);

// Convert rooms data to JSON for JavaScript
$roomsJson = json_encode($rooms);
?>

<div class="px-4 py-8">
    <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Call Log</h2>
            <p class="text-sm text-gray-600 mt-1">Showing entries for <span id="selectedDate"><?php echo date('d M Y', strtotime($selectedDate)); ?></span></p>
        </div>
        <div class="flex items-center gap-3">
            <input type="date" id="datePicker" 
                   class="bg-[#2a2a2a] text-white border border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500"
                   value="<?php echo $selectedDate; ?>"
                   onchange="changeDate(this.value)">
            <button onclick="openModal()" 
                    class="bg-blue-500 hover:bg-blue-700 text-white rounded-full w-10 h-10 flex items-center justify-center focus:outline-none focus:shadow-outline"
                    title="Add New Entry">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Modal -->
    <div id="callLogModal" class="fixed inset-0 bg-black bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative mx-auto p-6 w-11/12 max-w-4xl rounded-lg bg-[#1a1a1a] shadow-2xl mt-8">
            <div class="mb-6">
                <h3 class="text-2xl font-bold text-white">Add Call Log Entry</h3>
            </div>

            <form method="POST" class="space-y-6" id="callLogForm">
                <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                <input type="hidden" name="entry_id" id="entry_id" value="">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Form fields will go here -->
                </div>

                <div class="flex items-center justify-end mt-6 gap-2">
                    <button type="button" onclick="closeModal()" 
                            class="px-6 py-2.5 rounded text-white bg-gray-600 hover:bg-gray-700 focus:outline-none">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-6 py-2.5 rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                        Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bg-white bg-opacity-20 backdrop-filter backdrop-blur-lg rounded-xl shadow-lg p-4">
        <div class="overflow-x-auto">
            <table class="min-w-max bg-white bg-opacity-50 rounded-lg text-sm">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-2 py-2 whitespace-nowrap">Entry Date</th>
                        <th class="px-2 py-2">Guest Name</th>
                        <th class="px-2 py-2">Room #</th>
                        <th class="px-2 py-2">Booking Agency</th>
                        <th class="px-2 py-2 whitespace-nowrap">Arrival</th>
                        <th class="px-2 py-2 whitespace-nowrap">Departure</th>
                        <th class="px-2 py-2">Nights</th>
                        <th class="px-2 py-2">Time</th>
                        <th class="px-2 py-2">House Status</th>
                        <th class="px-2 py-2">Comments</th>
                        <th class="px-2 py-2">Associate Name</th>
                        <th class="px-2 py-2">Incident</th>
                        <th class="px-2 py-2">Department</th>
                        <th class="px-2 py-2">Follow-up</th>
                        <th class="px-2 py-2">Recovery Action</th>
                        <th class="px-2 py-2">Satisfaction</th>
                        <th class="px-2 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch()): ?>
                        <tr class="hover:bg-gray-100">
                            <td class="border px-2 py-1 whitespace-nowrap"><?php echo date('d M Y', strtotime($row['entry_date'])); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['guest_name']); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['room_no']); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['booking_agency']); ?></td>
                            <td class="border px-2 py-1 whitespace-nowrap"><?php echo date('d M Y', strtotime($row['arrival'])); ?></td>
                            <td class="border px-2 py-1 whitespace-nowrap"><?php echo date('d M Y', strtotime($row['departure'])); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['no_of_nights']); ?></td>
                            <td class="border px-2 py-1"><?php echo date('H:i', strtotime($row['time'])); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['house_status']); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['guest_comments']); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['associate_name']); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['incident']); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['department']); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['follow_up_by']); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['recovery_action']); ?></td>
                            <td class="border px-2 py-1"><?php echo htmlspecialchars($row['guest_satisfaction_level']); ?></td>
                            <td class="border px-4 py-2">
                                <div class="flex gap-3 justify-center">
                                    <button onclick="editEntry(<?php echo $row['id']; ?>)" 
                                            class="text-blue-500 hover:text-blue-700 focus:outline-none"
                                            title="Edit Entry">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button onclick="deleteEntry(<?php echo $row['id']; ?>)" 
                                            class="text-red-500 hover:text-red-700 focus:outline-none"
                                            title="Delete Entry">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// JavaScript code will go here
</script>

<?php require_once '../includes/footer.php'; ?>