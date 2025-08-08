<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAuth();

$stmt = $pdo->prepare("SELECT * FROM courtesy_report ORDER BY entry_date DESC, time DESC");
$stmt->execute();
$result = $stmt;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Courtesy Report</title>
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
        <h1 class="text-2xl font-bold">Courtesy Report</h1>
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
                <th class="px-4 py-2 border whitespace-nowrap">Comments</th>
                <th class="px-4 py-2 border whitespace-nowrap">Courtesy Type</th>
                <th class="px-4 py-2 border whitespace-nowrap">Time</th>
                <th class="px-4 py-2 border whitespace-nowrap">Staff Name</th>
            </tr>
        </thead>
        <tbody class="bg-gray-900 divide-y divide-gray-800">
            <?php while ($row = $result->fetch()): ?>
            <tr>
                <td class="px-4 py-2 border whitespace-nowrap"><?= date('d M Y', strtotime($row['entry_date'])) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['guest_name']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['room_no']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['comments']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['courtesy_type']) ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= $row['time'] ? date('H:i', strtotime($row['time'])) : '' ?></td>
                <td class="px-4 py-2 border whitespace-nowrap"><?= htmlspecialchars($row['staff_name']) ?></td>
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