<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAuth();

$stmt = $pdo->prepare("SELECT * FROM guest_interactions ORDER BY entry_date DESC, time DESC");
$stmt->execute();
$result = $stmt;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Guest Comments Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    @media print {
        .print-hide { display: none !important; }
    }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
<?php include '../includes/sidebar.php'; ?>
<div class="p-8 w-full min-h-screen mx-auto transition-colors duration-200 overflow-x-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Guest Comments Report</h1>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded flex items-center gap-2 print-hide" title="Export/Print">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>
    <table class="min-w-max bg-white bg-opacity-10 rounded-lg text-sm w-full">
        <thead class="bg-blue-800 text-white">
            <tr>
                <th class="px-4 py-2 border whitespace-nowrap">Date</th>
                <th class="px-4 py-2 border whitespace-nowrap">Guest Name</th>
                <th class="px-4 py-2 border whitespace-nowrap">Room No</th>
                <th class="px-4 py-2 border whitespace-nowrap">Booking Agency</th>
                <th class="px-4 py-2 border whitespace-nowrap">Arrival</th>
                <th class="px-4 py-2 border whitespace-nowrap">Departure</th>
                <th class="px-4 py-2 border whitespace-nowrap">No. of Nights</th>
                <th class="px-4 py-2 border whitespace-nowrap">Time</th>
                <th class="px-4 py-2 border whitespace-nowrap">House Status</th>
                <th class="px-4 py-2 border whitespace-nowrap">Guest Comments</th>
                <th class="px-4 py-2 border whitespace-nowrap">Associate Name</th>
                <th class="px-4 py-2 border whitespace-nowrap">Incident</th>
                <th class="px-4 py-2 border whitespace-nowrap">Department</th>
                <th class="px-4 py-2 border whitespace-nowrap">Follow Up By</th>
                <th class="px-4 py-2 border whitespace-nowrap">Recovery Action</th>
                <th class="px-4 py-2 border whitespace-nowrap">Guest Satisfaction Level</th>
            </tr>
        </thead>
        <tbody class="bg-gray-900 divide-y divide-gray-800">
            <?php while ($row = $result->fetch()): ?>
            <tr>
                <td class="px-4 py-2 border whitespace-nowrap"><?= date('d M Y', strtotime($row['entry_date'])) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['guest_name']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['room_no']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['booking_agency']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= $row['arrival'] ? date('d M Y', strtotime($row['arrival'])) : '' ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= $row['departure'] ? date('d M Y', strtotime($row['departure'])) : '' ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['no_of_nights']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= $row['time'] ? date('H:i', strtotime($row['time'])) : '' ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['house_status']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['guest_comments']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['associate_name']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['incident']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['department']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['follow_up_by']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['recovery_action']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['guest_satisfaction_level']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script>
function changeDate(date) {
    window.location.href = `?date=${date}`;
}
</script>
</body>
</html>