<?php
header('Content-Type: application/json');

// Get the selected date from the query parameter
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Create response array
$response = [
    'headerDate' => date('l d-M-Y', strtotime($selectedDate)),
    'dates' => []
];

// Generate dates for the next 7 days starting from selected date
for ($i = 0; $i <= 7; $i++) {
    $date = strtotime($selectedDate . " +$i days");
    $response['dates'][] = [
        'date' => date('d-M-y', $date),
        'day' => date('D', $date)
    ];
}

// Return JSON response
echo json_encode($response);