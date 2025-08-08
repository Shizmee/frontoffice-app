<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/sidebar.php';

// Initialize variables
$success_message = '';
$error_message = '';

// Generate a new form token if it doesn't exist
if (!isset($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_token']) && isset($_SESSION['form_token']) 
    && $_POST['form_token'] === $_SESSION['form_token']) {
    
    // Clear the token to prevent resubmission
    unset($_SESSION['form_token']);
    
    try {
        $data = [
            'guest_name' => $_POST['guest_name'],
            'room_no' => $_POST['room_no'],
            'booking_agency' => $_POST['booking_agency'],
            'arrival' => $_POST['arrival'],
            'departure' => $_POST['departure'],
            'no_of_nights' => $_POST['no_of_nights'],
            'time' => $_POST['time'],
            'house_status' => $_POST['house_status'],
            'guest_comments' => $_POST['guest_comments'],
            'associate_name' => $_POST['associate_name'],
            'incident' => $_POST['incident'],
            'department' => $_POST['department'],
            'follow_up_by' => $_POST['follow_up_by'],
            'recovery_action' => $_POST['recovery_action'],
            'guest_satisfaction_level' => $_POST['guest_satisfaction_level']
        ];

        if (isset($_POST['interaction_id'])) {
            // Update existing entry
            $data['id'] = $_POST['interaction_id'];
            $sql = "UPDATE guest_interactions SET 
                    guest_name = :guest_name,
                    room_no = :room_no,
                    booking_agency = :booking_agency,
                    arrival = :arrival,
                    departure = :departure,
                    no_of_nights = :no_of_nights,
                    time = :time,
                    house_status = :house_status,
                    guest_comments = :guest_comments,
                    associate_name = :associate_name,
                    incident = :incident,
                    department = :department,
                    follow_up_by = :follow_up_by,
                    recovery_action = :recovery_action,
                    guest_satisfaction_level = :guest_satisfaction_level
                    WHERE id = :id";
        } else {
            // Insert new entry
            $sql = "INSERT INTO guest_interactions (
                    guest_name, room_no, booking_agency, arrival, 
                    departure, no_of_nights, time, house_status,
                    guest_comments, associate_name, incident, department,
                    follow_up_by, recovery_action, guest_satisfaction_level)
                    VALUES (
                    :guest_name, :room_no, :booking_agency, :arrival,
                    :departure, :no_of_nights, :time, :house_status,
                    :guest_comments, :associate_name, :incident, :department,
                    :follow_up_by, :recovery_action, :guest_satisfaction_level)";
        }

        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($data)) {
            $_SESSION['success_message'] = isset($_POST['interaction_id']) ? 
                "Interaction updated successfully!" : "Interaction added successfully!";
        } else {
            throw new Exception("Error saving interaction.");
        }

        // Preserve the date parameter when redirecting
        $date = isset($_GET['date']) ? '?date=' . $_GET['date'] : '';
        header('Location: ' . $_SERVER['PHP_SELF'] . $date);
        exit();
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get the selected date or default to today
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
// Validate the date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// Fetch interactions for the selected date
$stmt = $pdo->prepare("SELECT * FROM guest_interactions WHERE DATE(entry_date) = ? ORDER BY time DESC");
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
$roomsQuery = "SELECT Room_No, Guest_Name, TA_Name, Arrival_Date, Departure_Date, Room_Nights FROM fo_bob 
             WHERE Arrival_Date <= ? 
             AND Departure_Date >= ? 
             AND UPPER(Resv_Status) = 'CHECKED IN' 
             ORDER BY Room_No";

$roomsStmt = $pdo->prepare($roomsQuery);
$roomsStmt->execute([$selectedDate, $selectedDate]);
$rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);

$roomDetails = [];
foreach ($rooms as $room) {
    $roomDetails[$room['Room_No']] = [
        'guest_name' => $room['Guest_Name'],
        'booking_agency' => $room['TA_Name'] ?? '',
        'arrival' => $room['Arrival_Date'] ?? '',
        'departure' => $room['Departure_Date'] ?? '',
        'no_of_nights' => $room['Room_Nights'] ?? ''
    ];
}
$roomDetailsJson = json_encode($roomDetails);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Guest Interactions - Front Office System</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  /* Hide horizontal scrollbar on body */
  body {
    overflow-x: hidden;
  }
</style>
</head>
<body class="bg-gray-900 text-gray-100 transition-colors duration-200 min-h-screen relative" style="background-image: url('../assets/img/image.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div class="absolute inset-0 bg-black bg-opacity-70 z-0"></div>
    <div class="relative z-10">

<!-- Decorative background for all main pages -->
<!-- Removed gradient and extra overlays to show background image -->

<div class="p-8 bg-gray-900 bg-opacity-70 rounded-lg shadow-lg w-full min-h-screen mx-auto transition-colors duration-200 overflow-x-auto">
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
            <h2 class="text-2xl font-bold text-white">Guest Interactions</h2>
            <p class="text-sm text-white mt-1">Showing entries for <span id="selectedDate"><?php echo date('d M Y', strtotime($selectedDate)); ?></span></p>
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

    <table class="table-auto text-xs text-white min-w-full">
        <thead class="bg-blue-800 text-white">
            <tr>
                <th class="px-2 py-1 max-w-[90px] truncate" title="Entry Date">Entry Date</th>
                <th class="px-2 py-1 max-w-[150px] truncate" title="Guest Name">Guest Name</th>
                <th class="px-2 py-1 max-w-[50px] truncate" title="Room #">Room #</th>
                <th class="px-2 py-1 max-w-[200px] truncate" title="Booking Agency">Booking Agency</th>
                <th class="px-2 py-1 max-w-[100px] truncate" title="Arrival">Arrival</th>
                <th class="px-2 py-1 max-w-[100px] truncate" title="Departure">Departure</th>
                <th class="px-2 py-1 max-w-[40px] truncate" title="Nights">Nights</th>
                <th class="px-2 py-1 max-w-[60px] truncate" title="Time">Time</th>
                <th class="px-2 py-1 max-w-[100px] truncate" title="House Status">House Status</th>
                <th class="px-2 py-1 max-w-[150px] truncate" title="Comments">Comments</th>
                <th class="px-2 py-1 max-w-[120px] truncate" title="Associate Name">Associate Name</th>
                <th class="px-2 py-1 max-w-[120px] truncate" title="Incident">Incident</th>
                <th class="px-2 py-1 max-w-[100px] truncate" title="Department">Department</th>
                <th class="px-2 py-1 max-w-[100px] truncate" title="Follow-up">Follow-up</th>
                <th class="px-2 py-1 max-w-[150px] truncate" title="Recovery Action">Recovery Action</th>
                <th class="px-2 py-1 max-w-[90px] truncate" title="Satisfaction">Satisfaction</th>
                <th class="px-2 py-1 max-w-[80px] truncate" title="Actions">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch()): ?>
                <tr class="hover:bg-gray-700">
                    <td class="border px-2 py-1 max-w-[90px] truncate" title="<?php echo date('d M Y', strtotime($row['entry_date'])); ?>"><?php echo date('d M Y', strtotime($row['entry_date'])); ?></td>
                    <td class="border px-2 py-1 max-w-[150px] truncate" title="<?php echo htmlspecialchars($row['guest_name']); ?>"><?php echo htmlspecialchars($row['guest_name']); ?></td>
                    <td class="border px-2 py-1 max-w-[50px] truncate" title="<?php echo htmlspecialchars($row['room_no']); ?>"><?php echo htmlspecialchars($row['room_no']); ?></td>
                    <td class="border px-2 py-1 max-w-[200px] truncate" title="<?php echo htmlspecialchars($row['booking_agency']); ?>"><?php echo htmlspecialchars($row['booking_agency']); ?></td>
                    <td class="border px-2 py-1 max-w-[100px] truncate" title="<?php echo date('d M Y', strtotime($row['arrival'])); ?>"><?php echo date('d M Y', strtotime($row['arrival'])); ?></td>
                    <td class="border px-2 py-1 max-w-[100px] truncate" title="<?php echo date('d M Y', strtotime($row['departure'])); ?>"><?php echo date('d M Y', strtotime($row['departure'])); ?></td>
                    <td class="border px-2 py-1 max-w-[40px] truncate" title="<?php echo htmlspecialchars($row['no_of_nights']); ?>"><?php echo htmlspecialchars($row['no_of_nights']); ?></td>
                    <td class="border px-2 py-1 max-w-[60px] truncate" title="<?php echo date('H:i', strtotime($row['time'])); ?>"><?php echo date('H:i', strtotime($row['time'])); ?></td>
                    <td class="border px-2 py-1 max-w-[100px] truncate" title="<?php echo htmlspecialchars($row['house_status']); ?>"><?php echo htmlspecialchars($row['house_status']); ?></td>
                    <td class="border px-2 py-1 max-w-[150px] truncate" title="<?php echo htmlspecialchars($row['guest_comments']); ?>"><?php echo htmlspecialchars($row['guest_comments']); ?></td>
                    <td class="border px-2 py-1 max-w-[120px] truncate" title="<?php echo htmlspecialchars($row['associate_name']); ?>"><?php echo htmlspecialchars($row['associate_name']); ?></td>
                    <td class="border px-2 py-1 max-w-[120px] truncate" title="<?php echo htmlspecialchars($row['incident']); ?>"><?php echo htmlspecialchars($row['incident']); ?></td>
                    <td class="border px-2 py-1 max-w-[100px] truncate" title="<?php echo htmlspecialchars($row['department']); ?>"><?php echo htmlspecialchars($row['department']); ?></td>
                    <td class="border px-2 py-1 max-w-[100px] truncate" title="<?php echo htmlspecialchars($row['follow_up_by']); ?>"><?php echo htmlspecialchars($row['follow_up_by']); ?></td>
                    <td class="border px-2 py-1 max-w-[150px] truncate" title="<?php echo htmlspecialchars($row['recovery_action']); ?>"><?php echo htmlspecialchars($row['recovery_action']); ?></td>
                    <td class="border px-2 py-1 max-w-[90px] truncate" title="<?php echo htmlspecialchars($row['guest_satisfaction_level']); ?>"><?php echo htmlspecialchars($row['guest_satisfaction_level']); ?></td>
                    <td class="border px-2 py-1 max-w-[80px] truncate text-center">
                        <div class="flex gap-3 justify-center">
                            <button onclick="editInteraction(<?php echo $row['id']; ?>)" 
                                    class="text-blue-400 hover:text-blue-600 focus:outline-none"
                                    title="Edit Interaction">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <?php if (isAdmin() || isManager()): ?>
                            <button onclick="deleteInteraction(<?php echo $row['id']; ?>)" 
                                    class="text-red-400 hover:text-red-600 focus:outline-none"
                                    title="Delete Interaction">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Modal for Add/Edit Guest Interaction -->
    <div id="interactionModal" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 hidden">
        <div class="bg-gray-800 rounded-lg shadow-lg w-full max-w-4xl p-8 relative flex flex-col justify-center items-center">
            <button type="button" onclick="closeModal()" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 text-2xl font-bold">&times;</button>
            <h3 class="text-xl font-bold mb-4 text-gray-100" id="modalTitle">Add Guest Interaction</h3>
            <form id="interactionForm" method="POST" class="space-y-4 w-full">
                <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                <input type="hidden" name="interaction_id" id="interaction_id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="guest_name">Guest Name</label>
                        <input type="text" name="guest_name" id="guest_name" required class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter guest name">
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="room_no">Room No</label>
                        <select name="room_no" id="room_no" required class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center">
                            <option value="">Select Room</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['Room_No']); ?>">
                                    <?php echo htmlspecialchars($room['Room_No']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="booking_agency">Booking Agency</label>
                        <input type="text" name="booking_agency" id="booking_agency" class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter booking agency">
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="arrival">Arrival</label>
                        <input type="date" name="arrival" id="arrival" required class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Select arrival date">
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="departure">Departure</label>
                        <input type="date" name="departure" id="departure" required class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Select departure date">
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="no_of_nights">No. of Nights</label>
                        <input type="number" name="no_of_nights" id="no_of_nights" min="1" required class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter number of nights">
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="time">Time</label>
                        <input type="time" name="time" id="time" required class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Select time">
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="house_status">House Status</label>
                        <select name="house_status" id="house_status" class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center">
                            <option value="">Select status</option>
                            <option value="In-House">In-House</option>
                            <option value="Arrival">Arrival</option>
                            <option value="Departure">Departure</option>
                            <option value="Day-Use">Day-Use</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="guest_comments">Guest Comments</label>
                        <textarea name="guest_comments" id="guest_comments" rows="2" class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter guest comments"></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="associate_name">Associate Name</label>
                        <input type="text" name="associate_name" id="associate_name" class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter associate name">
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="incident">Incident</label>
                        <input type="text" name="incident" id="incident" class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter incident">
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="department">Department</label>
                        <input type="text" name="department" id="department" class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter department">
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="follow_up_by">Follow Up By</label>
                        <input type="text" name="follow_up_by" id="follow_up_by" class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter follow up by">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="recovery_action">Recovery Action</label>
                        <textarea name="recovery_action" id="recovery_action" rows="2" class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter recovery action"></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-700 dark:text-gray-200 text-sm font-semibold mb-1" for="guest_satisfaction_level">Guest Satisfaction Level</label>
                        <input type="text" name="guest_satisfaction_level" id="guest_satisfaction_level" class="w-full bg-gray-100 dark:bg-[#2a2a2a] text-gray-900 dark:text-white border border-gray-400 dark:border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500 text-center" placeholder="Enter satisfaction level">
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <button type="button" onclick="closeModal()" class="mr-3 px-4 py-2 rounded bg-gray-300 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-400 dark:hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
const roomDetails = <?php echo $roomDetailsJson; ?>;

document.addEventListener('DOMContentLoaded', function() {
    const roomNoSelect = document.querySelector('select[name="room_no"]');
    if (roomNoSelect) {
        roomNoSelect.addEventListener('change', function() {
            const val = this.value;
            if (roomDetails[val]) {
                document.querySelector('input[name="guest_name"]').value = roomDetails[val].guest_name || '';
                document.querySelector('input[name="booking_agency"]').value = roomDetails[val].booking_agency || '';
                document.querySelector('input[name="arrival"]').value = roomDetails[val].arrival || '';
                document.querySelector('input[name="departure"]').value = roomDetails[val].departure || '';
                document.querySelector('input[name="no_of_nights"]').value = roomDetails[val].no_of_nights || '';
            }
        });
    }
    // Set current time on time input click
    const timeInput = document.querySelector('input[name="time"]');
    if (timeInput) {
        timeInput.addEventListener('click', function() {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            this.value = `${hh}:${mm}`;
        });
    }
});

function openModal() {
    document.getElementById('interactionModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    document.getElementById('interactionModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('interactionForm').reset();
    document.getElementById('interaction_id').value = '';
}
function changeDate(date) {
    document.getElementById('selectedDate').textContent = new Date(date).toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
    window.location.href = `?date=${date}`;
}
// Add your other JS functions (editInteraction, deleteInteraction, etc.) here as needed
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html>
