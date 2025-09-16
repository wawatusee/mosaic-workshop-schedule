<?php

class CalendarModel {
    private $weeksPath;
    private $clientsPath;
    private $defaultSlots;
    private $closedDays;
    
    public function __construct($weeksPath, $clientsPath, $defaultSlots = [], $closedDays = []) {
        $this->weeksPath = rtrim($weeksPath, '/') . '/';
        $this->clientsPath = rtrim($clientsPath, '/') . '/';
        $this->defaultSlots = $defaultSlots;
        $this->closedDays = $closedDays;
        
        // Vérification et création des dossiers
        ensureDirectoryExists($this->weeksPath);
        ensureDirectoryExists($this->clientsPath);
    }
    
    /**
     * Récupère les données d'une semaine
     */
    public function getWeekData($week) {
        $weekFile = $this->weeksPath . $week . '.json';
        
        if (file_exists($weekFile)) {
            $content = file_get_contents($weekFile);
            $data = json_decode($content, true);
            
            if ($data === null) {
                error_log("Erreur de décodage JSON pour le fichier: $weekFile");
                return $this->generateEmptyWeek($week);
            }
            
            return $data;
        }
        
        // Génération d'une semaine vide si le fichier n'existe pas
        return $this->generateEmptyWeek($week);
    }
    
    /**
     * Sauvegarde les données d'une semaine
     */
    public function saveWeekData($week, $data) {
        $weekFile = $this->weeksPath . $week . '.json';
        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($jsonData === false) {
            error_log("Erreur d'encodage JSON pour la semaine: $week");
            return false;
        }
        
        return file_put_contents($weekFile, $jsonData) !== false;
    }
    
    /**
     * Génère une semaine vide avec les créneaux par défaut
     */
    private function generateEmptyWeek($week) {
        $weekData = [
            'week' => $week,
            'slots' => [
                'monday' => [],
                'tuesday' => [], 
                'wednesday' => [],
                'thursday' => [],
                'friday' => [],
                'saturday' => [],
                'sunday' => []
            ]
        ];
        
        // Application des créneaux par défaut pour chaque jour
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            if (!in_array($day, $this->closedDays)) {
                $weekData['slots'][$day] = $this->defaultSlots;
            }
        }
        
        return $weekData;
    }
    
    /**
     * Récupère les informations d'un client
     */
    public function getClientData($clientId) {
        $clientFile = $this->clientsPath . $clientId . '.json';
        
        if (file_exists($clientFile)) {
            $content = file_get_contents($clientFile);
            $data = json_decode($content, true);
            
            if ($data === null) {
                error_log("Erreur de décodage JSON pour le client: $clientId");
                return null;
            }
            
            return $data;
        }
        
        return null;
    }
    
    /**
     * Sauvegarde un nouveau client
     */
    public function saveClient($clientData) {
        $clientId = $this->generateClientId();
        $clientData['id'] = $clientId;
        $clientData['created'] = date('c'); // Format ISO 8601
        
        $clientFile = $this->clientsPath . $clientId . '.json';
        $jsonData = json_encode($clientData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($jsonData === false) {
            error_log("Erreur d'encodage JSON pour le client: $clientId");
            return false;
        }
        
        $result = file_put_contents($clientFile, $jsonData);
        
        return $result !== false ? $clientId : false;
    }
    
    /**
     * Génère un ID unique pour un client
     */
    private function generateClientId() {
        $attempts = 0;
        $maxAttempts = 100;
        
        do {
            $clientId = 'client_' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $clientFile = $this->clientsPath . $clientId . '.json';
            $attempts++;
            
            if ($attempts > $maxAttempts) {
                error_log("Impossible de générer un ID client unique après $maxAttempts tentatives");
                return false;
            }
            
        } while (file_exists($clientFile));
        
        return $clientId;
    }
    
    /**
     * Réserve un créneau
     */
    public function reserveSlot($week, $day, $time, $clientId) {
        $weekData = $this->getWeekData($week);
        
        if (!isset($weekData['slots'][$day])) {
            error_log("Jour invalide pour la réservation: $day");
            return false;
        }
        
        // Recherche et réservation du créneau
        foreach ($weekData['slots'][$day] as &$slot) {
            if ($slot['time'] === $time && $slot['status'] === 'available') {
                $slot['status'] = 'reserved';
                $slot['clientId'] = $clientId;
                $slot['confirmed'] = false;
                $slot['reservedAt'] = date('c');
                
                return $this->saveWeekData($week, $weekData);
            }
        }
        
        return false; // Créneau non trouvé ou non disponible
    }
    
    /**
     * Calcule les dates de la semaine
     */
    public function getWeekDates($week) {
        // Validation du format semaine
        if (!preg_match('/^(\d{4})-W(\d{2})$/', $week, $matches)) {
            error_log("Format de semaine invalide: $week");
            return false;
        }
        
        $year = $matches[1];
        $weekNum = $matches[2];
        
        try {
            // Premier jour de la semaine (lundi)
            $monday = new DateTime();
            $monday->setISODate($year, $weekNum);
            
            $dates = [];
            for ($i = 0; $i < 7; $i++) {
                $date = clone $monday;
                $date->modify("+$i day");
                $dates[] = $date;
            }
            
            return $dates;
            
        } catch (Exception $e) {
            error_log("Erreur lors du calcul des dates pour la semaine $week: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcule la semaine précédente
     */
    public function getPreviousWeek($week) {
        if (!preg_match('/^(\d{4})-W(\d{2})$/', $week, $matches)) {
            return false;
        }
        
        try {
            $year = $matches[1];
            $weekNum = $matches[2];
            
            $date = new DateTime();
            $date->setISODate($year, $weekNum);
            $date->modify('-1 week');
            
            return $date->format('Y-\WW');
            
        } catch (Exception $e) {
            error_log("Erreur lors du calcul de la semaine précédente: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcule la semaine suivante
     */
    public function getNextWeek($week) {
        if (!preg_match('/^(\d{4})-W(\d{2})$/', $week, $matches)) {
            return false;
        }
        
        try {
            $year = $matches[1];
            $weekNum = $matches[2];
            
            $date = new DateTime();
            $date->setISODate($year, $weekNum);
            $date->modify('+1 week');
            
            return $date->format('Y-\WW');
            
        } catch (Exception $e) {
            error_log("Erreur lors du calcul de la semaine suivante: " . $e->getMessage());
            return false;
        }
    }
}
?>