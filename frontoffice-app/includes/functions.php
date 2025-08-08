<?php
// Format date to human-readable format
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Format time to human-readable format
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Format date and time together
function formatDateTime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

// Generate status badge HTML
function getStatusBadge($status, $type = 'default') {
    $colors = [
        'open' => 'yellow',
        'in_progress' => 'blue',
        'closed' => 'green',
        'pending' => 'yellow',
        'resolved' => 'green',
        'confirmed' => 'green',
        'seated' => 'blue',
        'completed' => 'gray',
        'cancelled' => 'red',
        'booked' => 'blue'
    ];

    $color = $colors[$status] ?? 'gray';
    return sprintf(
        '<span class="px-2 py-1 text-xs rounded bg-%s-100 text-sm-%s">%s</span>',
        $color,
        $color,
        ucfirst($status)
    );
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Validate date format
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Clean and sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate pagination links
function generatePagination($currentPage, $totalPages, $urlPattern) {
    $html = '<div class="flex justify-center space-x-2 mt-4">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= sprintf(
            '<a href="%s" class="px-3 py-1 rounded border hover:bg-gray-100">&laquo;</a>',
            sprintf($urlPattern, $currentPage - 1)
        );
    }

    // Page numbers
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        if ($i === $currentPage) {
            $html .= sprintf(
                '<span class="px-3 py-1 rounded border bg-blue-600 text-white">%d</span>',
                $i
            );
        } else {
            $html .= sprintf(
                '<a href="%s" class="px-3 py-1 rounded border hover:bg-gray-100">%d</a>',
                sprintf($urlPattern, $i),
                $i
            );
        }
    }

    // Next button
    if ($currentPage < $totalPages) {
        $html .= sprintf(
            '<a href="%s" class="px-3 py-1 rounded border hover:bg-gray-100">&raquo;</a>',
            sprintf($urlPattern, $currentPage + 1)
        );
    }

    $html .= '</div>';
    return $html;
}
?>
