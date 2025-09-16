<?php
// Chargement de la configuration
require_once '../config/config.php';

// Chargement des classes
require_once SRC_PATH . '/model/calendar_model.php';
require_once SRC_PATH . '/view/calendar_view.php';

// Récupération de la semaine demandée (par défaut: semaine courante)
$weekParam = $_GET['week'] ?? date('Y-\WW');
$selectedWeek = validateWeek($weekParam);

// Initialisation du modèle calendrier avec les chemins et paramètres de config
$calendarModel = new CalendarModel(
    WEEKS_JSON_PATH,
    CLIENTS_JSON_PATH,
    $DEFAULT_SLOTS,
    $CLOSED_DAYS
);

// Chargement des données de la semaine
$weekData = $calendarModel->getWeekData($selectedWeek);

if ($weekData === false) {
    die('Erreur lors du chargement des données de la semaine');
}

// Génération de la vue
$calendarView = new CalendarView();
$calendarHtml = $calendarView->renderWeekCalendar($weekData, $selectedWeek, $calendarModel);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo WORKSHOP_NAME; ?> - Calendrier des créneaux</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/calendar.css">
</head>
<body>
    <header>
        <h1><?php echo WORKSHOP_NAME; ?></h1>
        <p>Réservez votre créneau dans notre atelier</p>
    </header>

    <main>
        <div class="calendar-container">
            <div class="calendar-navigation">
                <?php echo $calendarView->renderWeekNavigation($selectedWeek, $calendarModel); ?>
            </div>
            
            <?php if ($weekData): ?>
                <div class="calendar-week">
                    <?php echo $calendarHtml; ?>
                </div>
                
                <div class="legend">
                    <div class="legend-item">
                        <span class="status-available"></span> Disponible
                    </div>
                    <div class="legend-item">
                        <span class="status-reserved"></span> Réservé (en attente)
                    </div>
                    <div class="legend-item">
                        <span class="status-confirmed"></span> Confirmé
                    </div>
                </div>
            <?php else: ?>
                <div class="error-message">
                    <p>Impossible de charger le calendrier pour cette semaine.</p>
                    <a href="?week=<?php echo date('Y-\WW'); ?>">Retour à la semaine actuelle</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de réservation -->
    <div id="reservation-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Réserver ce créneau</h2>
            <div id="selected-slot-info"></div>
            
            <form id="reservation-form">
                <input type="hidden" id="slot-info" name="slot-info">
                
                <div class="form-group">
                    <label for="client-name">Nom complet *</label>
                    <input type="text" id="client-name" name="client-name" required>
                </div>
                
                <div class="form-group">
                    <label for="client-email">Email *</label>
                    <input type="email" id="client-email" name="client-email" required>
                </div>
                
                <div class="form-group">
                    <label for="client-phone">Téléphone</label>
                    <input type="tel" id="client-phone" name="client-phone">
                </div>
                
                <div class="form-group">
                    <label for="message">Message (optionnel)</label>
                    <textarea id="message" name="message" rows="3" placeholder="Précisez votre niveau, vos attentes..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-cancel">Annuler</button>
                    <button type="submit" class="btn-submit">Réserver</button>
                </div>
            </form>
        </div>
    </div>

    <div id="message-container"></div>

    <script src="js/calendar.js"></script>
    <script src="js/reservation.js"></script>
</body>
</html>

<?php
/**
 * Valide et nettoie le paramètre de semaine
 */
function validateWeek($weekParam) {
    // Validation du format semaine (YYYY-WXX)
    if (preg_match('/^\d{4}-W\d{2}$/', $weekParam)) {
        // Vérification que la semaine existe vraiment
        $matches = [];
        preg_match('/^(\d{4})-W(\d{2})$/', $weekParam, $matches);
        $year = (int)$matches[1];
        $week = (int)$matches[2];
        
        if ($year >= 2020 && $year <= 2030 && $week >= 1 && $week <= 53) {
            return $weekParam;
        }
    }
    
    // Retour semaine courante si invalide
    return date('Y-\WW');
}
?>