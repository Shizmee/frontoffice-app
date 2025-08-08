<?php
class MealCountCalculator {
    private $date;
    private $occupancyStats;
    private $departingGuests;
    private $inhouseGuests;

    public function __construct($date, $occupancyStats, $departingGuests, $inhouseGuests) {
        $this->date = $date;
        $this->occupancyStats = $occupancyStats;
        $this->departingGuests = $departingGuests;
        $this->inhouseGuests = $inhouseGuests;
    }

    private function countDeparturesBeforeTime($time) {
        $adultCount = 0;
        $childCount = 0;

        if (!empty($this->departingGuests['guests'])) {
            foreach ($this->departingGuests['guests'] as $guest) {
                // Convert departure time to 24-hour format without colon
                $depTime = isset($guest['dep_int']) ? str_replace(':', '', $guest['dep_int']) : '0000';
                if ($depTime <= $time) {
                    $adultCount += intval($guest['adult']);
                    $childCount += intval($guest['child']);
                }
            }
        }

        return [
            'adult_count' => $adultCount,
            'child_count' => $childCount
        ];
    }

    private function getPreviousDayCount() {
        // Get adult and child counts from previous day
        return [
            'adult_count' => $this->occupancyStats['previous_day']['adult_count'] ?? 0,
            'child_count' => $this->occupancyStats['previous_day']['child_count'] ?? 0
        ];
    }

    private function getCurrentInHouse() {
        return [
            'adult_count' => $this->inhouseGuests['totals']['adult'] ?? 0,
            'child_count' => $this->inhouseGuests['totals']['child'] ?? 0
        ];
    }

    public function calculateMealCounts() {
        error_log("Calculating meal counts for date: " . $this->date);
        
        $prevDay = $this->getPreviousDayCount();
        // For breakfast, check departures before 07:00
        $morningDepartures = $this->countDeparturesBeforeTime('0700');
        // For lunch, check departures before 12:00
        $afternoonDepartures = $this->countDeparturesBeforeTime('1200');
        $currentInHouse = $this->getCurrentInHouse();

        error_log("Previous day counts: " . print_r($prevDay, true));
        error_log("Morning departures (before 07:00): " . print_r($morningDepartures, true));
        error_log("Afternoon departures (before 12:00): " . print_r($afternoonDepartures, true));
        error_log("Current in-house: " . print_r($currentInHouse, true));

        // Calculate Breakfast counts (Previous day - departures before 07:00)
        $breakfast = [
            'adult' => intval($prevDay['adult_count']) - intval($morningDepartures['adult_count']),
            'child' => intval($prevDay['child_count']) - intval($morningDepartures['child_count'])
        ];
        $breakfast['total'] = $breakfast['adult'] + $breakfast['child'];

        // Calculate Lunch counts (Previous day - departures before 12:00)
        $lunch = [
            'adult' => intval($prevDay['adult_count']) - intval($afternoonDepartures['adult_count']),
            'child' => intval($prevDay['child_count']) - intval($afternoonDepartures['child_count'])
        ];
        $lunch['total'] = $lunch['adult'] + $lunch['child'];

        // Calculate Dinner counts (Current in-house)
        $dinner = [
            'adult' => intval($currentInHouse['adult_count']),
            'child' => intval($currentInHouse['child_count'])
        ];
        $dinner['total'] = $dinner['adult'] + $dinner['child'];

        $result = [
            'Breakfast' => $breakfast,
            'Lunch' => $lunch,
            'Dinner' => $dinner,
            'Meals' => [
                'Breakfast' => '07:00 to 10:00',
                'Lunch' => '12:00 to 14:30',
                'Dinner' => '19:00 to 21:30'
            ]
        ];

        error_log("Final meal counts: " . print_r($result, true));
        return $result;
    }
}
?>