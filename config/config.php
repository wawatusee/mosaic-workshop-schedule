<?php
// Configuration générale du site Calendar Vitraux

// Chemins de base
define('SITE_ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', SITE_ROOT . '/public');
define('CONFIG_PATH', SITE_ROOT . '/config');
define('SRC_PATH', SITE_ROOT . '/src');
define('JSON_PATH', SITE_ROOT . '/json');

// Chemins spécifiques JSON
define('WEEKS_JSON_PATH', JSON_PATH . '/weeks');
define('CLIENTS_JSON_PATH', JSON_PATH . '/clients');

// Configuration de l'atelier
define('WORKSHOP_NAME', 'Atelier Vitraux');
define('WORKSHOP_EMAIL', 'contact@atelier-vitraux.fr');

// Créneaux par défaut de l'atelier
$DEFAULT_SLOTS = [
    ['time' => '09:00', 'duration' => 2, 'status' => 'available'],
    ['time' => '11:00', 'duration' => 2, 'status' => 'available'], 
    ['time' => '14:00', 'duration' => 2, 'status' => 'available'],
    ['time' => '16:00', 'duration' => 2, 'status' => 'available']
];

// Jours de fermeture (dimanche par défaut)
$CLOSED_DAYS = ['sunday'];

// Fuseau horaire
date_default_timezone_set('Europe/Brussels');

// Fonction utilitaire pour créer les dossiers si nécessaire
function ensureDirectoryExists($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return is_dir($path);
}

// Création des dossiers JSON nécessaires
ensureDirectoryExists(WEEKS_JSON_PATH);
ensureDirectoryExists(CLIENTS_JSON_PATH);
?>