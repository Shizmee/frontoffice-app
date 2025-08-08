<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/data_fetchers/inhouse_fetcher.php';
require_once '../includes/data_fetchers/vip_fetcher.php';
require_once '../includes/data_fetchers/arrival_fetcher.php';
require_once '../includes/data_fetchers/departure_fetcher.php';
require_once '../includes/data_fetchers/overview_stats_fetcher.php';
require_once '../includes/data_fetchers/occupancy_stats_fetcher.php';
requireAuth();

// Get the selected date from URL parameter or default to tomorrow
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('tomorrow'));
$reportDate = date('l d-M-Y', strtotime($selectedDate));

// Store selected date in session for fetchers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['selected_report_date'] = $selectedDate;

// Fetch all required data first
$occupancyStats = getDailyOccupancyStats($selectedDate);
$todayStats = getOverviewStats($selectedDate);
$inhouseVIPData = getVIPInHouseGuests();
$arrivingVIPData = getVIPArrivingGuests();
$arrivingData = getArrivingGuests();
$departingData = getDepartingGuests();
$inhouseData = getInHouseGuests();

// Calculate meal counts with all available data
require_once '../includes/data_fetchers/meal_count_calculator.php';
$mealCalculator = new MealCountCalculator(
    $selectedDate,
    $occupancyStats,
    $departingData,
    $inhouseData
);
$mealCounts = $mealCalculator->calculateMealCounts();

function formatGuestNames($guest) {
    if (isset($guest['guest_names']) && is_array($guest['guest_names'])) {
        return implode("<br>", array_map('htmlspecialchars', $guest['guest_names']));
    } elseif (!empty($guest['guest_name'])) {
        // Split by titles (case-insensitive) and keep them
        $names = preg_split('/\s*(?=\b(?:Mr|Mrs|Miss|Ms|Dr|Prof|Master|Rev)\b)/i', $guest['guest_name']);
        $names = array_filter(array_map('trim', $names));
        return implode("<br>", array_map('htmlspecialchars', $names));
    }
    return '';
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Front Office System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
<style>
    @media print {
        /* Force all headers to have the brown color */
        th {
            background-color: #876432 !important;
            color: white !important;
            border: 1px solid #333 !important;
        }
        
        /* Ensure all table cells have borders */
        td {
            border: 1px solid #333 !important;
            background-color: white !important;
        }
        
        /* Make tables have strong borders */
        table {
            border: 2px solid #333 !important;
            margin-bottom: 10px !important;
        }
        
        /* Keep sub-headers beige */
        tr[style*="background-color: #876432"] th {
            background-color: #F6F0E2 !important;
            color: black !important;
        }
        /* Hide non-printable elements */
        .print-hide, 
        button, 
        input[type="date"],
        #excursionModal,
        .sidebar,
        #darkModeToggle,
        #menuToggle {
            display: none !important;
        }
        
        /* Show and style the header */
        .md\:hidden {
            display: block !important;
            background-color: #876432 !important;
            color: white !important;
            padding: 1rem !important;
            margin-bottom: 1rem !important;
        }
        
        /* Style the header title */
        .md\:hidden h1 {
            font-size: 1.5rem !important;
            font-weight: bold !important;
            color: white !important;
        }

        /* Ensure all borders and backgrounds */
        body, div, table, tr, td, th {
            background-color: white !important;
            color: black !important;
            border: 1px solid #333 !important;
            border-collapse: collapse !important;
        }
        
        /* Table specific borders */
        table {
            border: 2px solid #333 !important;
        }
        
        th, td {
            border: 1px solid #333 !important;
        }
        
        /* Main headers - brown color */
        [style*="background-color: #876432"] {
            background-color: #876432 !important;
            color: white !important;
        }

        /* Sub headers - beige color */
        [style*="background-color: #876432"] {
            background-color: #F6F0E2 !important;
            color: black !important;
        }

        /* Adjust font sizes for print */
        table {
            font-size: 8pt !important;
            width: 100% !important;
            margin-bottom: 10px !important;
        }

        th, td {
            padding: 2px 4px !important;
        }

        /* Optimize layout for fewer pages */
        .flex {
            display: flex !important;
            gap: 10px !important;
        }

        .flex-1 {
            flex: 1 !important;
        }

        /* Set landscape mode and margins */
        @page {
            size: landscape;
            margin: 0.5cm;
        }

        /* Ensure proper page breaks */
        table { page-break-inside: avoid !important; }
        tr { page-break-inside: avoid !important; }

        /* Adjust grid layout for print */
        .grid { 
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 10px !important;
        }

        /* Make guest lists more compact */
        .guest-list td,
        .guest-list th {
            font-size: 7pt !important;
            padding: 1px 2px !important;
        }

        /* Ensure headers are visible on all pages */
        thead {
            display: table-header-group !important;
        }

        /* Remove shadows and borders */
        .shadow, .rounded {
            box-shadow: none !important;
            border-radius: 0 !important;
        }

        /* Preserve section header colors */
        .section-header {
            border: 1px solid #333 !important;
            font-weight: bold !important;
        }

        /* Adjust spacing */
        .p-4, .p-6, .px-4, .py-2, .gap-4 {
            padding: 0 !important;
            margin: 5px 0 !important;
            gap: 5px !important;
        }

        /* Make text smaller in large tables */
        .guest-table {
            font-size: 7pt !important;
        }

        /* Optimize column widths */
        .w-20 { width: 40px !important; }
        .w-40 { width: 80px !important; }
        .w-16 { width: 30px !important; }
        .w-24 { width: 50px !important; }
        .w-32 { width: 60px !important; }
        .w-96 { width: 120px !important; }
    }
</style>
<?php include '../includes/sidebar.php'; ?>

<div class="ml-0 p-6 transition-all duration-300">
    <!-- Main Report Container -->
    <div class="bg-gray-900 text-gray-100 p-4 rounded shadow print:shadow-none">
        <!-- Report Header -->
        <div class="overview-header text-center flex flex-col items-center mb-4">
            <h1 class="text-2xl font-bold section-header" style="background: none; color: white; padding: 8px;">OVERVIEW</h1>
            <h2 class="text-xl" style="color: #F6F0E2; margin-bottom: 8px;"><?= $reportDate ?></h2>
        </div>
            <div class="flex items-center gap-4 mt-0">
                <input type="date" id="reportDate" class="bg-gray-800 border border-gray-700 text-gray-100 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-gray-600 print-hide" value="<?= $selectedDate ?>" onchange="updateDates(this.value)">
                <button onclick="openExcursionModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center gap-2 print-hide" title="Add Excursion">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button onclick="window.print()" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded flex items-center gap-2 print-hide" title="Print">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                    </svg>
                </button>
                <button onclick="exportOverviewPDF()" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded flex items-center gap-2 print-hide" title="Export to PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 4v4h4m0 0V4a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2v-4h-4z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>

            <!-- Excursion Modal -->
            <div id="excursionModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                <div class="bg-gray-900 p-6 rounded-lg shadow-xl w-full max-w-2xl">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-white">Add Excursion</h2>
                        <button onclick="closeExcursionModal()" class="text-gray-400 hover:text-gray-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <form id="excursionForm" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-300 mb-2">Room Number</label>
                                <input type="text" name="room_number" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-gray-300 mb-2">Guest Name</label>
                                <input type="text" name="guest_name" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-300 mb-2">Type</label>
                                <select name="excursion_type_id" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                                    <option value="">Select Type</option>
                                    <option value="1">Addu City Tour</option>
                                    <option value="2">Big Game Fishing</option>
                                    <option value="3">End of the Day Dolphin Cruise</option>
                                    <option value="4">Local Island Tour</option>
                                    <option value="5">Lucky Dolphin Cruise</option>
                                    <option value="6">Morning Fishing</option>
                                    <option value="7">Private Trip Tour</option>
                                    <option value="8">Snorkeling Explorer</option>
                                    <option value="9">Sunset Cocktail Cruise</option>
                                    <option value="10">Sunset Fishing</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-300 mb-2">Date</label>
                                <input type="date" name="date" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-300 mb-2">Time</label>
                                <input type="time" name="time" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-gray-300 mb-2">Paxs</label>
                                <input type="number" name="num_persons" min="1" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-gray-300 mb-2">Remarks</label>
                            <textarea name="notes" rows="2" class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2"></textarea>
                        </div>
                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" onclick="closeExcursionModal()" class="bg-gray-700 hover:bg-gray-600 text-white px-4 py-2 rounded">Cancel</button>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Save Excursion</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function openExcursionModal() {
                    document.getElementById('excursionModal').classList.remove('hidden');
                    document.getElementById('excursionModal').classList.add('flex');
                }

                function closeExcursionModal() {
                    document.getElementById('excursionModal').classList.add('hidden');
                    document.getElementById('excursionModal').classList.remove('flex');
                }

                document.getElementById('excursionForm').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    try {
                        const formData = new FormData(e.target);
                        
                        // Get and validate all form fields
                        const room_number = formData.get('room_number');
                        const guest_name = formData.get('guest_name');
                        const excursion_type_id = formData.get('excursion_type_id');
                        const date = formData.get('date');
                        const time = formData.get('time');
                        const num_persons = formData.get('num_persons');
                        const notes = formData.get('notes');

                        // Validate required fields
                        if (!room_number || !guest_name || !excursion_type_id || !date || !time || !num_persons) {
                            throw new Error('Please fill in all required fields');
                        }

                        const data = {
                            room_number: room_number,
                            guest_name: guest_name,
                            excursion_type_id: parseInt(excursion_type_id),
                            date: date,
                            time: time,
                            num_persons: parseInt(num_persons),
                            notes: notes || ''
                        };

                        console.log('Sending data:', data);

                        // Get the current URL path up to frontoffice-app
                        const pathParts = window.location.pathname.split('/');
                        const appIndex = pathParts.indexOf('frontoffice-app');
                        const baseUrl = pathParts.slice(0, appIndex + 1).join('/');
                        const saveUrl = `${baseUrl}/ajax/save_excursion.php`;
                        console.log('Save URL:', saveUrl);

                        // Send the request
                        const response = await fetch(saveUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(data)
                        });

                        if (!response.ok) {
                            throw new Error('Server returned error: ' + response.status);
                        }

                        const responseText = await response.text();
                        console.log('Raw response:', responseText);

                        // Try to parse as JSON
                        let jsonData;
                        try {
                            jsonData = JSON.parse(responseText);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Raw response:', responseText);
                            throw new Error('Server returned invalid JSON. Raw response: ' + responseText);
                        }

                        if (jsonData.success) {
                            alert('Excursion saved successfully!');
                            closeExcursionModal();
                            location.reload();
                        } else {
                            throw new Error(jsonData.message || 'Unknown error occurred');
                        }
                    } catch (error) {
                        console.error('Error details:', error);
                        alert('Error saving excursion: ' + error.message);
                    }
                });
            </script>
        </div>

        <script>
        function updateDates(selectedDate) {
            // Redirect to the same page with the new date
            window.location.href = `?date=${selectedDate}`;
        }
        </script>

        <!-- Top Stats Grids -->
        <div class="flex gap-4 mb-4">
            <!-- Left table -->
            <div class="flex-1">
                <table class="w-full border-collapse border border-gray-700 text-sm bg-gray-900">
                    <tr style="background-color: #876432; color: black">
                        <th class="border border-gray-700 p-1 text-center">Details</th>
                        <th class="border border-gray-700 p-1 text-center">Rooms</th>
                        <th class="border border-gray-700 p-1 text-center">Pax</th>
                        <th class="border border-gray-700 p-1 text-center">%</th>
                    </tr>
                    <?php
                    // Occupancy stats already fetched at the top of the file
                    ?>
                    <tr>
                        <td class="border border-gray-700 p-1 bg-gray-900">Previous Day Count</td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= $occupancyStats['previous_day']['rooms'] ?></td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= $occupancyStats['previous_day']['total_guests'] ?></td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= round($occupancyStats['previous_day']['occupancy_percentage']) ?>%</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-700 p-1 bg-gray-900">Today's Occupancy</td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= $occupancyStats['today']['rooms'] ?></td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= $occupancyStats['today']['total_guests'] ?></td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= round($occupancyStats['today']['occupancy_percentage']) ?>%</td>
                    </tr>
                    <?php
                    // Today's stats already fetched at the top of the file
                    ?>
                    <tr>
                        <td class="border border-gray-700 p-1 bg-gray-900">Today's Arrivals</td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= $todayStats['arr_rooms'] ?? 0 ?></td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= $todayStats['arrival_pax'] ?? 0 ?></td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900">-</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-700 p-1 bg-gray-900">Today's Departure</td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= $todayStats['dep_rooms'] ?? 0 ?></td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= $todayStats['departure_pax'] ?? 0 ?></td>
                        <td class="border border-gray-700 p-1 text-center bg-gray-900">-</td>
                    </tr>
                </table>
            </div>

            <!-- Right table -->
            <div class="flex-1">
                <?php
                    // Function to get room type stats
                    function getRoomTypeStats($date) {
                        global $pdo;
                        try {
                            $stmt = $pdo->prepare("
                                WITH RoomData AS (
                                    SELECT 
                                        CASE 
                                            WHEN MARKET_CODE IN ('PSRV', 'SRV') THEN 'SRV'
                                            WHEN MARKET_CODE IN ('PSBV', 'SBV') THEN 'SBV'
                                            WHEN MARKET_CODE IN ('PBVW', 'PSRVW') THEN 'PBVW'
                                            ELSE MARKET_CODE 
                                        END as room_type,
                                        OCCUPIED_ROOMS,
                                        AVAILABLE_ROOMS
                                    FROM room_type 
                                    WHERE DATE(BUSINESS_DATE) = ?
                                    AND MARKET_CODE NOT IN ('0', 'Hotel Availability')
                                )
                                SELECT 
                                    room_type,
                                    SUM(OCCUPIED_ROOMS) as occupied,
                                    SUM(AVAILABLE_ROOMS) as available
                                FROM RoomData
                                WHERE room_type IN ('SRV', 'SBV', 'PBVW')
                                GROUP BY room_type
                                ORDER BY 
                                    CASE room_type
                                        WHEN 'SRV' THEN 1
                                        WHEN 'SBV' THEN 2
                                        WHEN 'PBVW' THEN 3
                                        ELSE 4
                                    END
                            ");
                            $stmt->execute([$date]);
                            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            // Calculate totals
                            $totals = [
                                'occupied' => 0,
                                'available' => 0
                            ];
                            foreach ($results as $row) {
                                $totals['occupied'] += $row['occupied'];
                                $totals['available'] += $row['available'];
                            }
                            
                            return [
                                'stats' => $results,
                                'totals' => $totals
                            ];
                        } catch (PDOException $e) {
                            error_log("Error fetching room type stats: " . $e->getMessage());
                            return ['stats' => [], 'totals' => ['occupied' => 0, 'available' => 0]];
                        }
                    }

                    $roomTypeStats = getRoomTypeStats($selectedDate);
                    ?>
                    <table class="w-full border-collapse border border-gray-700 text-sm bg-gray-900">
                        <tr style="background-color: #876432; color: black">
                            <th class="border border-gray-700 p-1 text-center">Type</th>
                            <th class="border border-gray-700 p-1 text-center">Occupied Rooms</th>
                            <th class="border border-gray-700 p-1 text-center">Available Rooms</th>
                        </tr>
                        <?php foreach ($roomTypeStats['stats'] as $stat): ?>
                        <tr>
                            <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($stat['room_type']) ?></td>
                            <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= htmlspecialchars($stat['occupied']) ?></td>
                            <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= htmlspecialchars($stat['available']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td class="border border-gray-700 p-1 bg-gray-900">Total</td>
                            <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= htmlspecialchars($roomTypeStats['totals']['occupied']) ?></td>
                            <td class="border border-gray-700 p-1 text-center bg-gray-900"><?= htmlspecialchars($roomTypeStats['totals']['available']) ?></td>
                        </tr>
                    </table>
            </div>
        </div>

        <!-- Two tables side by side container -->
        <div class="flex gap-4 mb-4">
            <!-- Meal Plan Summary Table -->
            <div class="flex-1">
                <table class="w-full border-collapse border border-gray-700 text-sm bg-gray-900">
                    <tr style="background-color: #876432; color: black">
                        <th class="border border-gray-700 p-1 text-center">MP</th>
                        <th class="border border-gray-700 p-1 text-center">Pax</th>
                        <th class="border border-gray-700 p-1 text-center">%</th>
                    </tr>
                    <?php
                    require_once '../includes/data_fetchers/meal_plan_counter.php';
                    $mealPlanCounts = getMealPlanCounts();
                    $mealPlans = ['BB', 'HB', 'FB', 'AI'];
                    
                    $totalPax = 0;
                    foreach ($mealPlans as $plan) {
                        $totalPax += $mealPlanCounts['Total'][$plan];
                    }
                    
                    foreach ($mealPlans as $plan) {
                        $pax = $mealPlanCounts['Total'][$plan];
                        $percentage = $totalPax > 0 ? round(($pax / $totalPax) * 100, 1) : 0;
                        
                        echo "<tr class='border-b border-gray-700'>";
                        echo "<td class='border border-gray-700 p-1'>{$plan}</td>";
                        echo "<td class='border border-gray-700 p-1 text-center'>{$pax}</td>";
                        echo "<td class='border border-gray-700 p-1 text-center'>{$percentage}%</td>";
                        echo "</tr>";
                    }
                    ?>
                </table>
            </div>

            <!-- Meal Plan Count Table -->
            <div class="flex-1">
                <table class="w-full border-collapse border border-gray-700 text-sm bg-gray-900">
                    <tr style="background-color: #876432; color: black">
                        <th class="border border-gray-700 p-1 text-center">MP COUNT</th>
                        <th class="border border-gray-700 p-1 text-center">INH</th>
                        <th class="border border-gray-700 p-1 text-center">ARR</th>
                        <th class="border border-gray-700 p-1 text-center">DEP</th>
                        <th class="border border-gray-700 p-1 text-center">Total</th>
                    </tr>
                    <?php
                    foreach ($mealPlans as $plan) {
                        echo "<tr class='border-b border-gray-700'>";
                        echo "<td class='border border-gray-700 p-1'>{$plan}</td>";
                        echo "<td class='border border-gray-700 p-1 text-center'>{$mealPlanCounts['INH'][$plan]}</td>";
                        echo "<td class='border border-gray-700 p-1 text-center'>{$mealPlanCounts['ARR'][$plan]}</td>";
                        echo "<td class='border border-gray-700 p-1 text-center'>{$mealPlanCounts['DEP'][$plan]}</td>";
                        echo "<td class='border border-gray-700 p-1 text-center'>{$mealPlanCounts['Total'][$plan]}</td>";
                        echo "</tr>";
                    }
                    ?>
                </table>
            </div>
        </div>
            </table>
        </div>

        <!-- Main Stats Grid -->
        <div class="mb-4">
            <table class="w-full border-collapse border text-sm">
                <tr style="background-color: #876432; color: black">
                    <th class="border border-gray-700 p-1 text-center" style="background-color: #876432; color: black">DATE</th>
                    <?php
                    // Show current date plus next 7 days with day name
                    for ($i = 0; $i <= 7; $i++) {   
                        $date = strtotime($selectedDate . " +$i days");
                        echo "<th class='border p-1 text-center date-column'>" . 
                             date('d-M-y', $date) . 
                             "<br>" . 
                             date('D', $date) . 
                             "</th>";
                    }
                    ?>
                </tr>
                <?php
                // Get stats for the date range starting from selected date
                $start_date = $selectedDate;
                $end_date = date('Y-m-d', strtotime($selectedDate . ' +7 days'));
                $stats = getOverviewStatsRange($start_date, $end_date);

                // Helper function to format occupancy percentage
                function formatOccupancy($value) {
                    return round($value) . '%';
                }

                // Define rows and their corresponding data keys
                $rows = [
                    ['label' => 'Occupancy', 'key' => 'occupancy', 'format' => 'formatOccupancy'],
                    ['label' => 'Occ Rooms', 'key' => 'occ_rooms'],
                    ['label' => 'Arr Rooms', 'key' => 'arr_rooms'],
                    ['label' => 'Arr Pax', 'key' => 'arrival_pax'],
                    ['label' => 'Dep rooms', 'key' => 'dep_rooms'],
                    ['label' => 'Dep Pax', 'key' => 'departure_pax'],
                    ['label' => 'In House', 'key' => 'inhouse_pax']
                ];

                // Generate rows
                foreach ($rows as $row) {
                    echo "<tr>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900'>{$row['label']}</td>";
                    
                    for ($i = 0; $i <= 7; $i++) {
                        $date = date('Y-m-d', strtotime($selectedDate . " +$i days"));
                        $value = isset($stats[$date][$row['key']]) ? $stats[$date][$row['key']] : 0;
                        
                        if (isset($row['format'])) {
                            $formatter = $row['format'];
                            $displayValue = $formatter($value);
                        } else {
                            $displayValue = $value;
                        }
                        
                        echo "<td class='border p-1 text-center'>{$displayValue}</td>";
                    }
                    
                    echo "</tr>";
                }
                ?>
        </table>
    </div>

        <!-- Meal Time and Nationality Stats -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <!-- Side by Side Tables -->
            <div class="grid grid-cols-2 gap-4 col-span-2">
                <!-- Meal Count -->
                <table class="w-full border-collapse border text-sm guest-table">
                    <tr style="background-color: #F6F0E2; color: black">
                        <th class="border border-gray-700 p-1 text-center" colspan="4" style="background-color: #876432; color: white">Meal Count</th>
                    </tr>
                    <tr style="background-color: #F6F0E2; color: black">
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">MP</th>
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">ADULT</th>
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">CHILD</th>
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">Total</th>
                    </tr>
                    <?php
                    $breakfast = $mealCounts['Breakfast'];
                    $lunch = $mealCounts['Lunch'];
                    $dinner = $mealCounts['Dinner'];
                    // Breakfast row
                    echo "<tr>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900'>Breakfast (07:00 to 10:00)</td>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900 text-center'>{$breakfast['adult']}</td>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900 text-center'>{$breakfast['child']}</td>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900 text-center'>{$breakfast['total']}</td>";
                    echo "</tr>";
                    // Lunch row
                    echo "<tr>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900'>Lunch (12:00 to 14:30)</td>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900 text-center'>{$lunch['adult']}</td>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900 text-center'>{$lunch['child']}</td>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900 text-center'>{$lunch['total']}</td>";
                    echo "</tr>";
                    // Dinner row
                    echo "<tr>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900'>Dinner (19:00 to 21:30)</td>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900 text-center'>{$dinner['adult']}</td>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900 text-center'>{$dinner['child']}</td>";
                    echo "<td class='border border-gray-700 p-1 bg-gray-900 text-center'>{$dinner['total']}</td>";
                    echo "</tr>";
                    ?>
                </table>

                <!-- Top Nationalities Pax -->
                <table class="w-full border-collapse border border-gray-700 text-sm bg-gray-900">
                    <tr style="background-color: #F6F0E2; color: black">
                        <th class="border border-gray-700 p-1 text-center" colspan="2" style="background-color: #876432; color: white">Top Nationalities Pax</th>
                    </tr>
                    <?php
                    require_once '../includes/data_fetchers/nationality_fetcher.php';
                    $nationalityData = getTopNationalities();
                    
                    foreach ($nationalityData['nationalities'] as $nationality) {
                        echo "<tr>";
                        echo "<td class='border border-gray-700 p-1 bg-gray-900'>{$nationality['country']}</td>";
                        echo "<td class='border border-gray-700 p-1 text-center bg-gray-900'>{$nationality['guest_count']}</td>";
                        echo "</tr>";
                    }
                    ?>
                    <tr style="background-color: #F6F0E2; color: black">
                        <td class="border border-gray-700 p-1">Paxs</td>
                        <td class="border border-gray-700 p-1 text-center"><?= $nationalityData['total'] ?></td>
                    </tr>
        </table>
            </div>
        </div>

        <!-- Weather Info -->
        <div class="text-sm border-t pt-2">
            <div class="grid grid-cols-4 text-center">
                <div>Sunrise & Sunset:<br>05:53Hrs & 18:02 Hrs</div>
                <div>Tide Low:<br>21:00 Hrs & 22:24 Hrs</div>
                <div>Tide High:<br>16:30 Hrs & 17:00Hrs</div>
                <div>Temperature: 29C<br>Humidity: 79%</div>
            </div>
    </div>

        <!-- Events/Excursion Section -->
        <div class="mt-4">
            <table class="w-full border-collapse border text-sm">
                <tr>
                    <th colspan="6" class="border border-gray-700 p-1 text-center" style="background-color: #876432; color: white">
                        EVENTS / EXCURSION (<?= date('d-M-Y', strtotime($selectedDate)) ?>)
                    </th>
                </tr>
                        <tr style="background-color: #F6F0E2; color: black">
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">Room</th>
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">Guest Name</th>
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">Type</th>
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">Time</th>
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">Pax</th>
                        <th class="border border-gray-700 p-1 text-center" style="background-color: #F6F0E2; color: black">Remarks</th>
                    </tr>
                <?php
                require_once '../includes/data_fetchers/excursion_fetcher.php';
                // Get excursions for selected date
                $excursions = getExcursions($selectedDate);
                
                if (empty($excursions)): ?>
                    <tr>
                        <td colspan="6" class="border border-gray-700 p-1 bg-gray-900 text-center">No excursions scheduled</td>
                    </tr>
                <?php else:
                    foreach ($excursions as $excursion): ?>
                        <tr>
                            <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($excursion['room_number']) ?></td>
                            <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($excursion['guest_name']) ?></td>
                            <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($excursion['excursion_type']) ?></td>
                            <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($excursion['time']) ?></td>
                            <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($excursion['num_persons']) ?></td>
                            <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($excursion['remarks']) ?></td>
                        </tr>
                    <?php endforeach;
                endif; ?>
        </table>
    </div>

        <!-- Guest Lists -->
        <?php
        // All guest data already fetched at the top of the file

        // Reorder the $sections array so ARRIVALS comes before DEPARTURES
        $sections = [
            'INHOUSE VIP' => [
                'title' => 'Total Inhouse VIP',
                'data' => $inhouseVIPData
            ],
            'ARRIVAL VIP' => [
                'title' => 'Total Arrival VIP',
                'data' => $arrivingVIPData
            ],
            'ARRIVALS' => [
                'title' => 'Total Arrival',
                'data' => $arrivingData
            ],
            'DEPARTURES' => [
                'title' => 'Total Departure',
                'data' => $departingData
            ],
            'GUESTS IN HOUSE' => [
                'title' => 'Total Inhouse',
                'data' => $inhouseData
            ]
        ];

        foreach ($sections as $title => $info) {
            $guests = $info['data']['guests'];
            $totals = $info['data']['totals'];
            ?>
            <div class="mt-4">
                <table class="w-full border-collapse border text-sm guest-table">
                    <tr>
                        <th colspan="17" class="border border-gray-700 p-1 text-center" style="background-color: #876432; color: white"><?= $title ?></th>
                    </tr>
                    <tr style="background-color: #F6F0E2; color: black">
                        <?php if ($title === 'GUESTS IN HOUSE'): ?>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">Room</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-40">Name</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">Adult</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">Child</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">MP</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-32">TA Name</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">RN</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">VIP</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">Type</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">Country</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">ARR INT</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">ARR DATE</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">DEP DATE</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">DEP INT</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">SPRQ</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-96">Comments</th>
                        <?php elseif ($title === 'ARRIVALS'): ?>
                            <th class="border border-blue-800 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">Room</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-40">Name</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">Adult</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">Child</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">MP</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-32">TA Name</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">RN</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">VIP</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">Type</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">Country</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">ARR INT</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">DOM TIME</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">ARR DATE</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">DEP DATE</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">DEP INT</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">SPRQ</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-96">Comments</th>
                        <?php else: ?>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">Room</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-40">Name</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">Adult</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">Child</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">MP</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-32">TA Name</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">RN</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-16">VIP</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">Type</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">Country</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">ARR INT</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">DOM TIME</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">ARR DATE</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-24">DEP DATE</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">DEP INT</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-20">SPRQ</th>
                            <th class="border border-gray-700 p-1 text-center whitespace-nowrap" style="background-color: #F6F0E2; color: black whitespace-nowrap w-96">Comments</th>
                        <?php endif; ?>
                    </tr>
                    <?php if (empty($guests)): ?>
                        <tr>
                            <td colspan="17" class="border border-gray-700 p-1 bg-gray-900 text-center">No records found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($guests as $guest): ?>
                            <tr>
                                <?php if ($title === 'GUESTS IN HOUSE'): ?>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['room_no']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= isset($guest['guest_names']) ? implode("<br>", array_map('htmlspecialchars', $guest['guest_names'])) : htmlspecialchars($guest['guest_name']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['adult']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['child']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['meal_plan']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['ta_name']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['room_night']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['vip']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['room_type']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['country']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= preg_replace('/\s+\d{2}:\d{2}$/', '', htmlspecialchars($guest['arr_int'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center whitespace-nowrap"><?= date('d-m-y', strtotime($guest['arr_date'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center whitespace-nowrap"><?= date('d-m-y', strtotime($guest['dep_date'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= preg_replace('/\s+\d{2}:\d{2}$/', '', htmlspecialchars($guest['dep_int'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['sprq']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 whitespace-normal"><?= htmlspecialchars($guest['comments']) ?></td>
                                <?php elseif ($title === 'ARRIVALS'): ?>
                                    <td class="border border-blue-800 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['room_no']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= isset($guest['guest_names']) ? implode("<br>", array_map('htmlspecialchars', $guest['guest_names'])) : htmlspecialchars($guest['guest_name']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['adult']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['child']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['meal_plan']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['ta_name']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['room_night']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['vip']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['room_type']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['country']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= preg_replace('/\s+\d{2}:\d{2}$/', '', htmlspecialchars($guest['arr_int'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= isset($guest['dom_time_to_go']) ? htmlspecialchars($guest['dom_time_to_go']) : '' ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center whitespace-nowrap"><?= date('d-m-y', strtotime($guest['arr_date'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center whitespace-nowrap"><?= date('d-m-y', strtotime($guest['dep_date'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center leading-[1px]"><?= $guest['dep_int'] ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['sprq']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 whitespace-normal"><?= htmlspecialchars($guest['comments']) ?></td>
                                <?php else: ?>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['room_no']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= isset($guest['guest_names']) ? implode("<br>", array_map('htmlspecialchars', $guest['guest_names'])) : htmlspecialchars($guest['guest_name']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['adult']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['child']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['meal_plan']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['ta_name']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['room_night']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= htmlspecialchars($guest['vip']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['room_type']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['country']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= preg_replace('/\s+\d{2}:\d{2}$/', '', htmlspecialchars($guest['arr_int'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= isset($guest['dom_time_to_go']) ? htmlspecialchars($guest['dom_time_to_go']) : '' ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center whitespace-nowrap"><?= date('d-m-y', strtotime($guest['arr_date'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center whitespace-nowrap"><?= date('d-m-y', strtotime($guest['dep_date'])) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 text-center leading-[1px]"><?= $guest['dep_int'] ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900"><?= htmlspecialchars($guest['sprq']) ?></td>
                                    <td class="border border-gray-700 p-1 bg-gray-900 whitespace-normal"><?= htmlspecialchars($guest['comments']) ?></td>
                                <?php endif; ?>
                            </tr>
                <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="bg-gray-800">
                        <td colspan="2" class="border border-gray-700 p-1 bg-gray-900 font-bold"><?= $info['title'] ?></td>
                        <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= $totals['adult'] ?></td>
                        <td class="border border-gray-700 p-1 bg-gray-900 text-center"><?= $totals['child'] ?></td>
                        <td colspan="13" class="border border-gray-700 p-1 bg-gray-900"></td>
                    </tr>
                </table>
            </div>
            <?php
        }
        ?>
    </div>
</div>

<script>
function exportOverviewPDF() {
    window.print();
}
</script>
<style>
@media print {
    .print-hide { display: none !important; }
    .print-only { display: none !important; }
    #menuToggle { display: none !important; }
    .overview-header { display: block !important; page-break-before: avoid !important; }
    /* Only change header background color for print */
    th[style*="background-color: #876432"],
    tr[style*="background-color: #876432"] th,
    .section-header { background-color: #876432 !important; color: white !important; }
    /* Do NOT change border or other text colors */
}
.section-header { background-color: #876432; color: white; }
</style>
</body>
</html>