<?php

class RequestModel {
    private $requestsPath;
    
    public function __construct($requestsPath) {
        $this->requestsPath = rtrim($requestsPath, '/') . '/';
        ensureDirectoryExists($this->requestsPath);
    }
    
    /**
     * Crée une nouvelle demande de réservation
     */
    public function createRequest($requestData) {
        $requestId = $this->generateRequestId();
        
        $request = [
            'id' => $requestId,
            'slot' => $requestData['slot'],
            'client' => $requestData['client'],
            'fullContact' => $requestData['fullContact'] ?? null,
            'status' => 'pending',
            'createdAt' => date('c'),
            'processedAt' => null,
            'processedBy' => null
        ];
        
        $requestFile = $this->requestsPath . $requestId . '.json';
        $jsonData = json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($jsonData === false) {
            error_log("Erreur d'encodage JSON pour la demande: $requestId");
            return false;
        }
        
        if (file_put_contents($requestFile, $jsonData) !== false) {
            return $requestId;
        }
        
        return false;
    }
    
    /**
     * Récupère une demande par son ID
     */
    public function getRequest($requestId) {
        $requestFile = $this->requestsPath . $requestId . '.json';
        
        if (file_exists($requestFile)) {
            $content = file_get_contents($requestFile);
            $data = json_decode($content, true);
            
            if ($data === null) {
                error_log("Erreur de décodage JSON pour la demande: $requestId");
                return null;
            }
            
            return $data;
        }
        
        return null;
    }
    
    /**
     * Met à jour une demande
     */
    public function updateRequest($requestId, $requestData) {
        $requestFile = $this->requestsPath . $requestId . '.json';
        $jsonData = json_encode($requestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($jsonData === false) {
            error_log("Erreur d'encodage JSON pour la mise à jour: $requestId");
            return false;
        }
        
        return file_put_contents($requestFile, $jsonData) !== false;
    }
    
    /**
     * Liste toutes les demandes avec filtrage optionnel
     */
    public function getAllRequests($status = null) {
        $requests = [];
        
        if (!is_dir($this->requestsPath)) {
            return $requests;
        }
        
        $files = glob($this->requestsPath . 'req_*.json');
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $request = json_decode($content, true);
            
            if ($request === null) {
                error_log("Erreur de décodage JSON pour le fichier: $file");
                continue;
            }
            
            // Filtrer par statut si spécifié
            if ($status === null || $request['status'] === $status) {
                $requests[] = $request;
            }
        }
        
        // Trier par date de création (plus récents en premier)
        usort($requests, function($a, $b) {
            return strtotime($b['createdAt']) - strtotime($a['createdAt']);
        });
        
        return $requests;
    }
    
    /**
     * Approuve une demande et met à jour le calendrier
     */
    public function approveRequest($requestId, $approvedBy, $finalClientName) {
        $request = $this->getRequest($requestId);
        if (!$request) {
            return false;
        }
        
        // Mettre à jour le statut de la demande
        $request['status'] = 'approved';
        $request['processedAt'] = date('c');
        $request['processedBy'] = $approvedBy;
        $request['finalClientName'] = $finalClientName;
        
        return $this->updateRequest($requestId, $request);
    }
    
    /**
     * Rejette une demande
     */
    public function rejectRequest($requestId, $rejectedBy, $reason = '') {
        $request = $this->getRequest($requestId);
        if (!$request) {
            return false;
        }
        
        // Mettre à jour le statut de la demande
        $request['status'] = 'rejected';
        $request['processedAt'] = date('c');
        $request['processedBy'] = $rejectedBy;
        $request['rejectionReason'] = $reason;
        
        return $this->updateRequest($requestId, $request);
    }
    
    /**
     * Supprime une demande
     */
    public function deleteRequest($requestId) {
        $requestFile = $this->requestsPath . $requestId . '.json';
        
        if (file_exists($requestFile)) {
            return unlink($requestFile);
        }
        
        return false;
    }
    
    /**
     * Génère un ID unique pour une demande
     */
    private function generateRequestId() {
        $attempts = 0;
        $maxAttempts = 1000;
        
        do {
            $requestId = 'req_' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $requestFile = $this->requestsPath . $requestId . '.json';
            $attempts++;
            
            if ($attempts > $maxAttempts) {
                error_log("Impossible de générer un ID de demande unique après $maxAttempts tentatives");
                return false;
            }
            
        } while (file_exists($requestFile));
        
        return $requestId;
    }
    
    /**
     * Statistiques des demandes
     */
    public function getRequestsStats() {
        $allRequests = $this->getAllRequests();
        
        $stats = [
            'total' => count($allRequests),
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'initiated_clients' => 0,
            'new_clients' => 0
        ];
        
        foreach ($allRequests as $request) {
            $stats[$request['status']]++;
            
            if ($request['client']['isInitiated']) {
                $stats['initiated_clients']++;
            } else {
                $stats['new_clients']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Nettoie les anciennes demandes (plus de X jours)
     */
    public function cleanupOldRequests($daysOld = 30) {
        $cutoffDate = date('c', strtotime("-$daysOld days"));
        $requests = $this->getAllRequests();
        $cleaned = 0;
        
        foreach ($requests as $request) {
            // Supprimer les demandes traitées (approuvées ou rejetées) anciennes
            if (in_array($request['status'], ['approved', 'rejected']) 
                && $request['processedAt'] < $cutoffDate) {
                
                if ($this->deleteRequest($request['id'])) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}
?>