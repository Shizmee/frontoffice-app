<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAuth();

// Handle AJAX requests first, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'delete_entry') {
        if (!isAdmin() && !isManager()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Permission denied.']);
            exit;
        }
        try {
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('Invalid ID');
            }
            $id = $_POST['id'];
            
            $stmt = $pdo->prepare("DELETE FROM buggy_log WHERE id = ?");
            if (!$stmt->execute([$id])) {
                throw new Exception('Failed to delete entry');
            }
            
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } elseif ($_POST['action'] === 'get_entry') {
        try {
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('Invalid ID');
            }
            $id = $_POST['id'];
            
            $stmt = $pdo->prepare("SELECT * FROM buggy_log WHERE id = ?");
            if (!$stmt->execute([$id])) {
                throw new Exception('Failed to fetch entry');
            }
            
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$entry) {
                throw new Exception('Entry not found');
            }
            
            echo json_encode(['success' => true, 'entry' => $entry]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } elseif ($_POST['action'] === 'update_time_done') {
        try {
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('Invalid ID');
            }
            $id = $_POST['id'];
            
            // Use the time sent from the client
            $time_done = isset($_POST['time']) ? $_POST['time'] : date('H:i');
            
            $stmt = $pdo->prepare("UPDATE buggy_log SET time_done = ? WHERE id = ?");
            if (!$stmt->execute([$time_done, $id])) {
                throw new Exception('Failed to update time');
            }
            
            echo json_encode(['success' => true, 'time' => $time_done]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } elseif ($_POST['action'] === 'update_status') {
        try {
            $id = $_POST['id'];
            $status = $_POST['status'];
            $stmt = $pdo->prepare("UPDATE buggy_log SET status_followup = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    } elseif ($_POST['action'] === 'update_fup') {
        try {
            $id = $_POST['id'];
            $fup = $_POST['fup'];
            $stmt = $pdo->prepare("UPDATE buggy_log SET fup_with_guest = ? WHERE id = ?");
            $stmt->execute([$fup, $id]);
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

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
        $date = $_POST['date'];
        $time = $_POST['time'];
        $call_from = $_POST['call_from'];
        $caller_name = $_POST['caller_name'];
        $guest_request = $_POST['guest_request'];
        $concern_department = $_POST['concern_department'];
        $comments = $_POST['comments'];
        $status_followup = $_POST['status_followup'];
        $time_done = $_POST['time_done'];
        $fup_with_guest = $_POST['fup_with_guest'];
        $fup_time = $_POST['fup_time'];

                if (isset($_POST['entry_id'])) {
                    // Update existing entry
                    $sql = "UPDATE buggy_log SET 
                            date = ?, time = ?, call_from = ?, caller_name = ?, guest_request = ?,
                            concern_department = ?, comments = ?, status_followup = ?, time_done = ?,
                            fup_with_guest = ?, fup_time = ?
                            WHERE id = ?";
                    
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute([$date, $time, $call_from, $caller_name, $guest_request,
                            $concern_department, $comments, $status_followup, $time_done, $fup_with_guest, 
                            $fup_time, $_POST['entry_id']])) {
                        $_SESSION['success_message'] = "Call log entry updated successfully!";
                    } else {
                        throw new Exception("Error updating call log entry.");
                    }
                } else {
                    // Insert new entry
                    $sql = "INSERT INTO buggy_log (date, time, call_from, caller_name, guest_request, 
                            concern_department, comments, status_followup, time_done, fup_with_guest, fup_time) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute([$date, $time, $call_from, $caller_name, $guest_request,
                            $concern_department, $comments, $status_followup, $time_done, $fup_with_guest, $fup_time])) {
                        $_SESSION['success_message'] = "Call log entry added successfully!";
                    } else {
                        throw new Exception("Error adding call log entry.");
                    }
                }
                
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Get messages from session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message
}

// Include header and sidebar after processing form
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Handle status updates via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $id = $_POST['id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE buggy_log SET status_followup = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        exit;
    } elseif ($_POST['action'] === 'update_fup') {
        $id = $_POST['id'];
        $fup = $_POST['fup'];
        $stmt = $pdo->prepare("UPDATE buggy_log SET fup_with_guest = ? WHERE id = ?");
        $stmt->execute([$fup, $id]);
        exit;
    } elseif ($_POST['action'] === 'update_time_done') {
        try {
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('Invalid ID');
            }
            $id = $_POST['id'];
            $time_done = date('H:i:s');
            
            $stmt = $pdo->prepare("UPDATE buggy_log SET time_done = ? WHERE id = ?");
            if (!$stmt->execute([$time_done, $id])) {
                throw new Exception('Failed to update time');
            }
            
            echo date('H:i', strtotime($time_done)); // Return formatted time
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo "Error: " . $e->getMessage();
            exit;
        }
    }
}

// Fetch buggy log entries for today only
$selectedDate = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM buggy_log WHERE date = ? ORDER BY time DESC");
$stmt->execute([$selectedDate]);
$result = $stmt;

// Define status and FUP options
$statusOptions = ['Informed', 'Transferred', 'Handled', 'Pending', 'FUp'];
$fupOptions = ['Picked Up', 'Done'];

// Fetch checked-in rooms and guest names
$today = date('Y-m-d');
// Debug: Check the current date being used
error_log("Today's date: " . $today);

$roomsQuery = "SELECT Room_No, Guest_Name FROM fo_bob 
             WHERE Arrival_Date <= ? 
             AND Departure_Date >= ? 
             AND UPPER(Resv_Status) = 'CHECKED IN' 
             ORDER BY Room_No";

// Debug: Log the query
error_log("Room query: " . $roomsQuery);

$roomsStmt = $pdo->prepare($roomsQuery);
$roomsStmt->execute([$today, $today]);
$rooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Log the number of rooms found
error_log("Number of rooms found: " . count($rooms));

// Debug: Check if there are any records in fo_bob at all
$checkQuery = "SELECT COUNT(*) as total FROM fo_bob";
$checkStmt = $pdo->query($checkQuery);
$totalRecords = $checkStmt->fetch(PDO::FETCH_ASSOC)['total'];
error_log("Total records in fo_bob: " . $totalRecords);

// Debug: Get a sample of current records
$sampleQuery = "SELECT Room_No, Guest_Name, Arrival_Date, Departure_Date, Resv_Status 
                FROM fo_bob LIMIT 5";
$sampleRecords = $pdo->query($sampleQuery)->fetchAll(PDO::FETCH_ASSOC);
error_log("Sample records: " . print_r($sampleRecords, true));

// Convert rooms data to JSON for JavaScript
$roomsJson = json_encode($rooms);
?>
<!-- Decorative background for buggy-log page -->
<div class="fixed inset-0 -z-10">
  <div class="absolute inset-0 bg-gradient-to-br from-blue-200 via-purple-100 to-pink-200 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900 opacity-80"></div>
  <div class="absolute top-1/4 left-1/2 w-[60vw] h-[60vw] bg-white bg-opacity-20 rounded-full blur-3xl -translate-x-1/2 -z-10"></div>
</div>

<body class="bg-gray-900 text-gray-100 transition-colors duration-200 min-h-screen relative" style="background-image: url('../assets/img/image.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div class="absolute inset-0 bg-black bg-opacity-70 z-0"></div>
    <div class="relative z-10">
        <div class="container mx-auto px-4 py-8">
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

            
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Call Log Entries</h2>
                        <p class="text-sm text-gray-600 mt-1">Showing entries for <span id="selectedDateLabel"><?php echo date('d M Y', strtotime($selectedDate)); ?></span></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="date" id="datePicker" name="datePicker" value="<?php echo $selectedDate; ?>" class="bg-[#2a2a2a] text-white border border-gray-600 rounded py-2 px-3 focus:outline-none focus:border-blue-500" onchange="changeDate(this.value)">
                        <button onclick="openModal()" class="bg-blue-500 hover:bg-blue-700 text-white rounded-full w-10 h-10 flex items-center justify-center focus:outline-none focus:shadow-outline" title="Add New Entry">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                    <table class="min-w-max bg-white bg-opacity-50 rounded-lg text-sm w-full">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-4 py-2 border whitespace-nowrap" style="min-width: 100px;">Date</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Time</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Call From</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Caller Name</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Guest Complain/Request</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Concern Department</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Comments</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Status/Follow up</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Time Done</th>
                                <th class="px-4 py-2 border whitespace-nowrap">FUP with guest</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Waited Time</th>
                                <th class="px-4 py-2 border whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-900 divide-y divide-gray-800">
                            <?php while ($row = $result->fetch()): ?>
                                <tr class="hover:bg-gray-800">
                                    <td class="px-4 py-2 border whitespace-nowrap" style="min-width: 100px;"><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                    <td class="px-4 py-2 border whitespace-nowrap"><?php echo date('H:i', strtotime($row['time'])); ?></td>
                                    <td class="px-4 py-2 border whitespace-nowrap"><?php echo htmlspecialchars($row['call_from']); ?></td>
                                    <td class="px-4 py-2 border whitespace-nowrap"><?php echo htmlspecialchars($row['caller_name']); ?></td>
                                    <td class="px-4 py-2 border whitespace-nowrap"><?php echo htmlspecialchars($row['guest_request']); ?></td>
                                    <td class="px-4 py-2 border whitespace-nowrap"><?php echo htmlspecialchars($row['concern_department']); ?></td>
                                    <td class="px-4 py-2 border whitespace-nowrap"><?php echo htmlspecialchars($row['comments']); ?></td>
                                    <td class="px-4 py-2 border whitespace-nowrap">
                                        <select onchange="updateStatus(this, <?php echo $row['id']; ?>)" 
                                                class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-1 px-2">
                                            <option value="">Select Status</option>
                                            <?php foreach ($statusOptions as $option): ?>
                                                <option value="<?php echo $option; ?>" 
                                                    <?php echo $row['status_followup'] === $option ? 'selected' : ''; ?>>
                                                    <?php echo $option; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2 border whitespace-nowrap cursor-pointer" onclick="updateTimeDone(this, <?php echo $row['id']; ?>)">
                                        <?php echo $row['time_done'] ? date('H:i', strtotime($row['time_done'])) : '--:--'; ?>
                                    </td>
                                    <td class="px-4 py-2 border whitespace-nowrap">
                                        <select onchange="updateFUP(this, <?php echo $row['id']; ?>)"
                                                class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-1 px-2">
                                            <option value="">Select Status</option>
                                            <?php foreach ($fupOptions as $option): ?>
                                                <option value="<?php echo $option; ?>"
                                                    <?php echo $row['fup_with_guest'] === $option ? 'selected' : ''; ?>>
                                                    <?php echo $option; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2 border whitespace-nowrap">
                                        <?php
                                        if ($row['time_done'] && $row['time']) {
                                            $time_done = strtotime($row['time_done']);
                                            $start_time = strtotime($row['time']);
                                            $diff_minutes = round(($time_done - $start_time) / 60);
                                            
                                            // Format as HH:mm
                                            $hours = floor($diff_minutes / 60);
                                            $minutes = $diff_minutes % 60;
                                            echo sprintf("%02d:%02d", $hours, $minutes);
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-2 border whitespace-nowrap">
                                        <div class="flex gap-3 justify-center">
                                            <button onclick="editEntry(<?php echo $row['id']; ?>)" 
                                                    class="text-blue-500 hover:text-blue-700 focus:outline-none"
                                                    title="Edit Entry">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <?php if (isAdmin() || isManager()): ?>
                                            <button onclick="deleteEntry(<?php echo $row['id']; ?>)" 
                                                    class="text-red-500 hover:text-red-700 focus:outline-none"
                                                    title="Delete Entry">
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
                </div>
            </div>

            <!-- Modal -->
            <div id="entryModal" class="fixed inset-0 bg-black bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
                <div class="relative mx-auto p-6 w-11/12 max-w-4xl rounded-lg bg-[#1a1a1a] shadow-2xl mt-8">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-white" id="modalTitle">Add Entry</h3>
                    </div>

                    <form method="POST" class="space-y-6" id="buggyLogForm">
                        <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-white text-sm font-semibold mb-2" for="date">Date</label>
                                <input type="date" name="date" id="date" required readonly
                                       value="<?php echo date('Y-m-d'); ?>"
                                       class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 focus:outline-none focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-white text-sm font-semibold mb-2" for="time">Time</label>
                                <div class="relative">
                                    <input type="time" name="time" id="time" required onclick="setCurrentTime(this)"
                                           class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 pr-10 focus:outline-none focus:border-blue-500 cursor-pointer">
                                    <span class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-white text-sm font-semibold mb-2" for="call_from">Call From</label>
                                <select name="call_from" id="call_from" required
                                       class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 focus:outline-none focus:border-blue-500">
                                    <option value="">Select Room</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?php echo htmlspecialchars($room['Room_No']); ?>" 
                                                data-guest="<?php echo htmlspecialchars($room['Guest_Name']); ?>">
                                            <?php echo htmlspecialchars($room['Room_No']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-white text-sm font-semibold mb-2" for="caller_name">Caller Name</label>
                                <input type="text" name="caller_name" id="caller_name" required readonly
                                       class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 focus:outline-none focus:border-blue-500">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-white text-sm font-semibold mb-2" for="guest_request">Guest Complain/Request</label>
                                <textarea name="guest_request" id="guest_request" required rows="2"
                                       class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 focus:outline-none focus:border-blue-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-white text-sm font-semibold mb-2" for="concern_department">Concern Department</label>
                                <select name="concern_department" id="concern_department" required
                                       class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 focus:outline-none focus:border-blue-500">
                                    <option value="">Select Department</option>
                                    <option value="Management">Management</option>
                                    <option value="IT">IT</option>
                                    <option value="HR">HR</option>
                                    <option value="Clinic">Clinic</option>
                                    <option value="Finance">Finance</option>
                                    <option value="FO">FO</option>
                                    <option value="Reservation">Reservation</option>
                                    <option value="Sales & Mktg">Sales & Mktg</option>
                                    <option value="Purchasing">Purchasing</option>
                                    <option value="Sports">Sports</option>
                                    <option value="HK">HK</option>
                                    <option value="Garden">Garden</option>
                                    <option value="F&B">F&B</option>
                                    <option value="Kitchen">Kitchen</option>
                                    <option value="Spa">Spa</option>
                                    <option value="Security">Security</option>
                                    <option value="Transport">Transport</option>
                                    <option value="Engineering">Engineering</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-white text-sm font-semibold mb-2" for="comments">Comments</label>
                                <textarea name="comments" id="comments" rows="2"
                                       class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 focus:outline-none focus:border-blue-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-white text-sm font-semibold mb-2" for="status_followup">Status/Follow up</label>
                                <select name="status_followup" id="status_followup"
                                       class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 focus:outline-none focus:border-blue-500">
                                    <option value="">Select Status</option>
                                    <option value="Informed">Informed</option>
                                    <option value="Transferred">Transferred</option>
                                    <option value="Handled">Handled</option>
                                    <option value="Pending">Pending</option>
                                    <option value="FUp">FUp</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-white text-sm font-semibold mb-2" for="time_done">Time Done</label>
                                <div class="relative">
                                    <input type="time" name="time_done" id="time_done" onclick="setCurrentTime(this)"
                                           class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 pr-10 focus:outline-none focus:border-blue-500 cursor-pointer">
                                    <span class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-white text-sm font-semibold mb-2" for="fup_with_guest">FUP with guest</label>
                                <select name="fup_with_guest" id="fup_with_guest"
                                       class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 focus:outline-none focus:border-blue-500">
                                    <option value="">Select Status</option>
                                    <option value="Picked Up">Picked Up</option>
                                    <option value="Done">Done</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-white text-sm font-semibold mb-2" for="fup_time">FUP Time</label>
                                <div class="relative">
                                    <input type="time" name="fup_time" id="fup_time" onclick="setCurrentTime(this)"
                                           class="w-full bg-[#2a2a2a] text-white border border-gray-600 rounded py-2.5 px-3 pr-10 focus:outline-none focus:border-blue-500 cursor-pointer">
                                    <span class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </span>
                                </div>
                            </div>


                        </div>

                        <div class="flex items-center justify-end mt-6 gap-2">
                            <button type="button" onclick="closeModal()" 
                                    class="px-6 py-2.5 rounded text-white bg-gray-600 hover:bg-gray-700 focus:outline-none">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2.5 rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                                Add Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

<script>
function formatTimeToHHMM(timeString) {
    if (!timeString) return '';
    const [hours, minutes] = timeString.split(':');
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

function setCurrentTime(input) {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    input.value = `${hours}:${minutes}`;
}

// Format all time inputs to ensure HH:mm format
document.querySelectorAll('input[type="time"]').forEach(input => {
    input.addEventListener('change', function() {
        if (this.value) {
            const [hours, minutes] = this.value.split(':');
            this.value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
        }
    });
});

// Handle room selection and auto-fill guest name
document.getElementById('call_from').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const guestName = selectedOption.getAttribute('data-guest');
    document.getElementById('caller_name').value = guestName || '';
});

function openModal() {
    document.getElementById('entryModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('entryModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // Reset form and modal title
    document.getElementById('buggyLogForm').reset();
    document.getElementById('modalTitle').textContent = 'Add Entry';
    document.querySelector('#buggyLogForm button[type="submit"]').textContent = 'Add Entry';
    
    // Remove entry_id if it exists
    const idInput = document.querySelector('input[name="entry_id"]');
    if (idInput) {
        idInput.remove();
    }
}

// Form validation
document.getElementById('buggyLogForm').addEventListener('submit', function(e) {
    const requiredFields = ['date', 'time', 'call_from', 'caller_name', 'guest_request', 'concern_department', 'comments'];
    let isValid = true;
    let firstInvalidField = null;

    requiredFields.forEach(field => {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('border-red-500');
            if (!firstInvalidField) firstInvalidField = input;
        } else {
            input.classList.remove('border-red-500');
        }
    });

    if (!isValid) {
        e.preventDefault();
        firstInvalidField.focus();
        return false;
    }
});

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('entryModal');
    const modalContent = modal.querySelector('.relative');
    if (event.target === modal) {
        closeModal();
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
});

// Update status function
function updateStatus(selectElement, id) {
    const status = selectElement.value;
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('id', id);
    formData.append('status', status);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Error updating status');
    });
}

// Update FUP function
function updateFUP(selectElement, id) {
    const fup = selectElement.value;
    const formData = new FormData();
    formData.append('action', 'update_fup');
    formData.append('id', id);
    formData.append('fup', fup);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
    }).catch(error => {
        console.error('Error:', error);
        alert('Error updating FUP status');
    });
}

// Update time_done function
function updateTimeDone(cell, id) {
    if (!id) {
        console.error('No ID provided');
        return;
    }

    // Get current time
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;
    
    // Store original content in case of error
    const originalContent = cell.textContent;
    
    // Update display immediately
    cell.textContent = currentTime;
    
    // Calculate and update waited time immediately
    const row = cell.parentElement;
    const startTimeCell = row.querySelector('td:nth-child(2)'); // Time column
    const waitedTimeCell = row.querySelector('td:nth-child(11)'); // Waited Time column
    
    if (startTimeCell && waitedTimeCell) {
        const startTime = startTimeCell.textContent;
        if (startTime) {
            const [startHours, startMinutes] = startTime.split(':').map(Number);
            const diffMinutes = (now.getHours() * 60 + now.getMinutes()) - (startHours * 60 + startMinutes);
            const waitedHours = Math.floor(Math.abs(diffMinutes) / 60);
            const waitedMinutes = Math.abs(diffMinutes) % 60;
            waitedTimeCell.textContent = `${String(waitedHours).padStart(2, '0')}:${String(waitedMinutes).padStart(2, '0')}`;
        }
    }

    const formData = new FormData();
    formData.append('action', 'update_time_done');
    formData.append('id', id);
    formData.append('time', currentTime); // Send the current time to the server

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).then(async response => {
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Failed to update time');
        }
        return data.time;
    }).then(time => {
        if (!time) throw new Error('No time returned');
        
        cell.textContent = time;
        
        // Recalculate waited time
        try {
            const waitedTimeCell = cell.parentElement.querySelector('td:nth-last-child(1)');
            const startTimeCell = cell.parentElement.querySelector('td:nth-child(2)');
            
            if (!startTimeCell || !waitedTimeCell) {
                throw new Error('Could not find required cells');
            }

            const startTime = startTimeCell.textContent;
            const endTime = time;

            if (!startTime || !endTime) {
                throw new Error('Invalid time values');
            }

            const [startHours, startMinutes] = startTime.split(':').map(Number);
            const [endHours, endMinutes] = endTime.split(':').map(Number);

            if (isNaN(startHours) || isNaN(startMinutes) || isNaN(endHours) || isNaN(endMinutes)) {
                throw new Error('Invalid time format');
            }

            const startTotalMinutes = startHours * 60 + startMinutes;
            const endTotalMinutes = endHours * 60 + endMinutes;
            const diffMinutes = endTotalMinutes - startTotalMinutes;

            const hours = Math.floor(Math.abs(diffMinutes) / 60);
            const minutes = Math.abs(diffMinutes) % 60;
            waitedTimeCell.textContent = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
        } catch (calcError) {
            console.error('Error calculating waited time:', calcError);
            // Don't throw - we still successfully updated the time
        }
    }).catch(error => {
        console.error('Error:', error);
        cell.textContent = originalContent; // Restore original content
        alert(`Error updating time: ${error.message}`);
    });
}

function deleteEntry(id) {
    if (!confirm('Are you sure you want to delete this entry?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete_entry');
    formData.append('id', id);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the row from the table
            const row = document.querySelector(`button[onclick="deleteEntry(${id})"]`).closest('tr');
            row.remove();
        } else {
            throw new Error(data.error || 'Failed to delete entry');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting entry: ' + error.message);
    });
}

function editEntry(id) {
    const formData = new FormData();
    formData.append('action', 'get_entry');
    formData.append('id', id);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.error || 'Failed to fetch entry');
        }

        const entry = data.entry;
        
        // Fill the form with entry data
        document.getElementById('date').value = entry.date;
        document.getElementById('time').value = formatTimeToHHMM(entry.time);
        document.getElementById('call_from').value = entry.call_from;
        document.getElementById('caller_name').value = entry.caller_name;
        document.getElementById('guest_request').value = entry.guest_request;
        document.getElementById('concern_department').value = entry.concern_department;
        document.getElementById('comments').value = entry.comments;
        document.getElementById('status_followup').value = entry.status_followup;
        document.getElementById('time_done').value = formatTimeToHHMM(entry.time_done);
        document.getElementById('fup_with_guest').value = entry.fup_with_guest;
        document.getElementById('fup_time').value = formatTimeToHHMM(entry.fup_time);

        // Add the entry ID to the form
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'entry_id';
        idInput.value = entry.id;
        document.getElementById('buggyLogForm').appendChild(idInput);

        // Change the modal title and submit button text
        document.getElementById('modalTitle').textContent = 'Edit Entry';
        document.querySelector('#buggyLogForm button[type="submit"]').textContent = 'Update Entry';

        // Open the modal
        openModal();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error fetching entry: ' + error.message);
    });
}

function changeDate(date) {
    window.location.href = '?date=' + date;
}
</script>

<?php require_once '../includes/footer.php'; ?>