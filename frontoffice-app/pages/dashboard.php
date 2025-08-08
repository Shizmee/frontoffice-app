<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireAuth();

// Stats for today
$today = date('Y-m-d');

// Initialize counters
$open_calls = 0;
$today_interactions = 0;
$today_buggy = 0;

// Guest Calls Stats
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM guest_calls WHERE DATE(entry_date) = ? AND status = 'open'");
    $stmt->execute([$today]);
    $open_calls = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching guest calls: " . $e->getMessage());
}

// Guest Interactions Stats
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM guest_interactions WHERE DATE(entry_date) = ?");
    $stmt->execute([$today]);
    $today_interactions = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching guest interactions: " . $e->getMessage());
}

// Buggy Log Stats
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM buggy_log WHERE DATE(entry_date) = ?");
    $stmt->execute([$today]);
    $today_buggy = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching buggy logs: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard - Front Office System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
      /* Custom scrollbar for tables */
      ::-webkit-scrollbar {
        height: 8px;
        width: 8px;
      }
      ::-webkit-scrollbar-thumb {
        background-color: rgba(100, 100, 100, 0.4);
        border-radius: 4px;
      }
      ::-webkit-scrollbar-track {
        background-color: transparent;
      }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 transition-colors duration-200 min-h-screen relative" style="background-image: url('../assets/img/image.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <div class="absolute inset-0 bg-black bg-opacity-70 z-0"></div>
    <div class="relative z-10">

<!-- Removed gradient and extra overlays to show background image -->

<?php include '../includes/sidebar.php'; ?>

<div class="p-8 bg-gray-900 bg-opacity-70 rounded-lg shadow-lg w-full min-h-screen mx-auto transition-colors duration-200 overflow-x-auto">

    <p class="mb-6 text-lg">
        Welcome to the Front Office System dashboard. The background gradient shows through with a nice glass effect!
    </p>

    <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        <!-- Guest Calls Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden dark:bg-gray-800">
            <div class="p-4 bg-blue-500">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-white">Open Guest Calls</h3>
                        <p class="text-3xl font-bold text-white"><?= $open_calls ?></p>
                    </div>
                    <div class="ml-4">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="px-4 py-2 bg-blue-50">
                <a href="/frontoffice-app/pages/guest-calls.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View Details →</a>
            </div>
        </div>

        <!-- Guest Interactions Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden dark:bg-gray-800">
            <div class="p-4 bg-green-500">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-white">Today's Interactions</h3>
                        <p class="text-3xl font-bold text-white"><?= $today_interactions ?></p>
                    </div>
                    <div class="ml-4">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="px-4 py-2 bg-green-50">
                <a href="/frontoffice-app/pages/guest-interactions.php" class="text-green-600 hover:text-green-800 text-sm font-medium">View Details →</a>
            </div>
        </div>

        <!-- Buggy Log Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden dark:bg-gray-800">
            <div class="p-4 bg-purple-500">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-white">Today's Buggy Trips</h3>
                        <p class="text-3xl font-bold text-white"><?= $today_buggy ?></p>
                    </div>
                    <div class="ml-4">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="px-4 py-2 bg-purple-50">
                <a href="/frontoffice-app/pages/buggy-log.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium">View Details →</a>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Guest Calls -->
        <div class="bg-white rounded-lg shadow-md p-6 dark:bg-gray-800">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Recent Guest Calls</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="text-left p-2">Time</th>
                            <th class="text-left p-2">Room</th>
                            <th class="text-left p-2">Type</th>
                            <th class="text-left p-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT * FROM guest_calls WHERE DATE(entry_date) = ? ORDER BY time DESC LIMIT 5");
                            $stmt->execute([$today]);
                            while ($row = $stmt->fetch()):
                            ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="p-2"><?= date('H:i', strtotime($row['time'])) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['room_no']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['call_type']) ?></td>
                                <td class="p-2">
                                    <span class="px-2 py-1 text-xs rounded <?= $row['status'] == 'open' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                        <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile;
                        } catch (PDOException $e) {
                            error_log("Error fetching recent guest calls: " . $e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Guest Interactions -->
        <div class="bg-white rounded-lg shadow-md p-6 dark:bg-gray-800">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Recent Guest Interactions</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="text-left p-2">Time</th>
                            <th class="text-left p-2">Room</th>
                            <th class="text-left p-2">Guest Name</th>
                            <th class="text-left p-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT * FROM guest_interactions WHERE DATE(entry_date) = ? ORDER BY time DESC LIMIT 5");
                            $stmt->execute([$today]);
                            while ($row = $stmt->fetch()):
                            ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="p-2"><?= date('H:i', strtotime($row['time'])) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['room_no']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['guest_name']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['house_status']) ?></td>
                            </tr>
                            <?php endwhile;
                        } catch (PDOException $e) {
                            error_log("Error fetching recent guest interactions: " . $e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Buggy Logs -->
        <div class="bg-white rounded-lg shadow-md p-6 lg:col-span-2 dark:bg-gray-800">
            <h2 class="text-xl font-semibold mb-4 text-gray-800 dark:text-gray-100">Recent Buggy Trips</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="text-left p-2">Time</th>
                            <th class="text-left p-2">Room</th>
                            <th class="text-left p-2">Guest Name</th>
                            <th class="text-left p-2">From</th>
                            <th class="text-left p-2">To</th>
                            <th class="text-left p-2">Driver</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT * FROM buggy_log WHERE DATE(entry_date) = ? ORDER BY time DESC LIMIT 5");
                            $stmt->execute([$today]);
                            while ($row = $stmt->fetch()):
                            ?>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="p-2"><?= date('H:i', strtotime($row['time'])) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['room_no']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['guest_name']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['pickup_location']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['drop_location']) ?></td>
                                <td class="p-2"><?= htmlspecialchars($row['driver_name']) ?></td>
                            </tr>
                            <?php endwhile;
                        } catch (PDOException $e) {
                            error_log("Error fetching recent buggy logs: " . $e->getMessage());
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>
