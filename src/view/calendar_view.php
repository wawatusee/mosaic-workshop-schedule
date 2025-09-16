<?php

class CalendarView {
    private $daysLabels;
    private $monthsLabels;
    
    public function __construct() {
        $this->daysLabels = [
            'monday' => 'Lundi',
            'tuesday' => 'Mardi', 
            'wednesday' => 'Mercredi',
            'thursday' => 'Jeudi',
            'friday' => 'Vendredi',
            'saturday' => 'Samedi',
            'sunday' => 'Dimanche'
        ];
        
        $this->monthsLabels = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
    }
    
    /**
     * Génère le HTML du calendrier pour une semaine
     */
    public function renderWeekCalendar($weekData, $selectedWeek, $calendarModel) {
        $dates = $calendarModel->getWeekDates($selectedWeek);
        
        $html = '<div class="week-calendar">';
        
        $dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        for ($i = 0; $i < 7; $i++) {
            $dayKey = $dayKeys[$i];
            $date = $dates[$i];
            $daySlots = $weekData['slots'][$dayKey] ?? [];
            
            $html .= '<div class="day-column">';
            $html .= '<div class="day-header">';
            $html .= '<div class="day-name">' . $this->daysLabels[$dayKey] . '</div>';
            $html .= '<div class="day-date">' . $date->format('j') . ' ' . $this->monthsLabels[(int)$date->format('n')] . '</div>';
            $html .= '</div>';
            
            $html .= '<div class="day-slots">';
            
            if (empty($daySlots)) {
                $html .= '<div class="no-slots">Fermé</div>';
            } else {
                foreach ($daySlots as $slot) {
                    $html .= $this->renderSlot($slot, $selectedWeek, $dayKey);
                }
            }
            
            $html .= '</div>'; // day-slots
            $html .= '</div>'; // day-column
        }
        
        $html .= '</div>'; // week-calendar
        
        return $html;
    }
    
    /**
     * Génère le HTML d'un créneau
     */
    private function renderSlot($slot, $week, $day) {
        $statusClass = 'slot-' . $slot['status'];
        $isClickable = $slot['status'] === 'available';
        $clickableClass = $isClickable ? 'clickable' : '';
        
        $endTime = $this->calculateEndTime($slot['time'], $slot['duration']);
        
        $html = '<div class="time-slot ' . $statusClass . ' ' . $clickableClass . '"';
        
        if ($isClickable) {
            $slotData = json_encode([
                'week' => $week,
                'day' => $day,
                'time' => $slot['time'],
                'duration' => $slot['duration']
            ]);
            $html .= ' data-slot=\'' . htmlspecialchars($slotData) . '\'';
            $html .= ' onclick="openReservationModal(this)"';
        }
        
        $html .= '>';
        $html .= '<div class="time-range">' . $slot['time'] . ' - ' . $endTime . '</div>';
        $html .= '<div class="duration">(' . $slot['duration'] . 'h)</div>';
        
        // Statut
        switch ($slot['status']) {
            case 'available':
                $html .= '<div class="status">Disponible</div>';
                break;
            case 'reserved':
                $confirmed = $slot['confirmed'] ?? false;
                $statusText = $confirmed ? 'Confirmé' : 'Réservé';
                $html .= '<div class="status">' . $statusText . '</div>';
                break;
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Calcule l'heure de fin d'un créneau
     */
    private function calculateEndTime($startTime, $duration) {
        $start = DateTime::createFromFormat('H:i', $startTime);
        $start->modify('+' . $duration . ' hours');
        return $start->format('H:i');
    }
    
    /**
     * Génère la navigation entre semaines
     */
    public function renderWeekNavigation($selectedWeek, $calendarModel) {
        $previousWeek = $calendarModel->getPreviousWeek($selectedWeek);
        $nextWeek = $calendarModel->getNextWeek($selectedWeek);
        $currentWeek = date('Y-\WW');
        
        // Formatage de la semaine pour l'affichage
        $weekDisplay = $this->formatWeekDisplay($selectedWeek, $calendarModel);
        
        $html = '<div class="week-navigation">';
        $html .= '<a href="?week=' . $previousWeek . '" class="nav-btn prev-week">&larr; Semaine précédente</a>';
        $html .= '<div class="current-week">' . $weekDisplay . '</div>';
        $html .= '<a href="?week=' . $nextWeek . '" class="nav-btn next-week">Semaine suivante &rarr;</a>';
        
        if ($selectedWeek !== $currentWeek) {
            $html .= '<a href="?week=' . $currentWeek . '" class="nav-btn current-week-btn">Semaine actuelle</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Formate l'affichage de la semaine
     */
    private function formatWeekDisplay($week, $calendarModel) {
        $dates = $calendarModel->getWeekDates($week);
        
        $startDate = $dates[0]; // Lundi
        $endDate = $dates[6];   // Dimanche
        
        $startDay = $startDate->format('j');
        $endDay = $endDate->format('j');
        $startMonth = $this->monthsLabels[(int)$startDate->format('n')];
        $endMonth = $this->monthsLabels[(int)$endDate->format('n')];
        $year = $startDate->format('Y');
        
        if ($startDate->format('n') === $endDate->format('n')) {
            // Même mois
            return $startDay . ' - ' . $endDay . ' ' . $startMonth . ' ' . $year;
        } else {
            // Mois différents
            return $startDay . ' ' . $startMonth . ' - ' . $endDay . ' ' . $endMonth . ' ' . $year;
        }
    }
}
?>