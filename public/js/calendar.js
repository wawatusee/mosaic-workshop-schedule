/**
 * Calendar.js - Gestion des interactions du calendrier
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
});

/**
 * Initialise le calendrier
 */
function initializeCalendar() {
    // Ajouter des événements de clavier pour la navigation
    document.addEventListener('keydown', handleKeyboardNavigation);
    
    // Ajouter des tooltips aux créneaux
    addTooltips();
    
    // Marquer les créneaux passés
    markPastSlots();
    
    console.log('Calendrier initialisé');
}

/**
 * Navigation au clavier
 */
function handleKeyboardNavigation(e) {
    // Navigation avec les flèches
    if (e.key === 'ArrowLeft' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        const prevLink = document.querySelector('.prev-week');
        if (prevLink) {
            window.location.href = prevLink.href;
        }
    }
    
    if (e.key === 'ArrowRight' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        const nextLink = document.querySelector('.next-week');
        if (nextLink) {
            window.location.href = nextLink.href;
        }
    }
    
    // Retour à la semaine actuelle avec Ctrl+Home
    if (e.key === 'Home' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        const currentWeekLink = document.querySelector('.current-week-btn');
        if (currentWeekLink) {
            window.location.href = currentWeekLink.href;
        }
    }
}

/**
 * Ajoute des tooltips aux créneaux
 */
function addTooltips() {
    const slots = document.querySelectorAll('.time-slot');
    
    slots.forEach(slot => {
        const timeRange = slot.querySelector('.time-range');
        const duration = slot.querySelector('.duration');
        const status = slot.querySelector('.status');
        
        if (timeRange && duration && status) {
            const tooltip = `${timeRange.textContent} - ${duration.textContent} - ${status.textContent}`;
            slot.setAttribute('title', tooltip);
        }
        
        // Accessibilité : rendre les créneaux focusables
        if (slot.classList.contains('clickable')) {
            slot.setAttribute('tabindex', '0');
            slot.setAttribute('role', 'button');
            slot.setAttribute('aria-label', `Réserver le créneau ${timeRange ? timeRange.textContent : ''}`);
            
            // Permettre l'activation avec Entrée/Espace
            slot.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    slot.click();
                }
            });
        }
    });
}

/**
 * Marque visuellement les créneaux passés
 */
function markPastSlots() {
    const now = new Date();
    const today = now.toISOString().split('T')[0];
    const currentTime = now.getHours() * 60 + now.getMinutes(); // en minutes
    
    const dayColumns = document.querySelectorAll('.day-column');
    
    dayColumns.forEach((dayColumn, index) => {
        // Calculer la date de cette colonne (lundi = index 0)
        const dayDate = getDateFromDayIndex(index);
        
        if (dayDate < today) {
            // Jour passé : marquer tous les créneaux
            const slots = dayColumn.querySelectorAll('.time-slot.clickable');
            slots.forEach(slot => {
                slot.classList.add('past-slot');
                slot.classList.remove('clickable');
                slot.removeAttribute('onclick');
            });
        } else if (dayDate === today) {
            // Aujourd'hui : marquer les créneaux passés
            const slots = dayColumn.querySelectorAll('.time-slot.clickable');
            slots.forEach(slot => {
                const timeRange = slot.querySelector('.time-range');
                if (timeRange) {
                    const slotTime = parseTimeToMinutes(timeRange.textContent.split(' - ')[0]);
                    if (slotTime < currentTime) {
                        slot.classList.add('past-slot');
                        slot.classList.remove('clickable');
                        slot.removeAttribute('onclick');
                    }
                }
            });
        }
    });
}

/**
 * Obtient la date d'une colonne de jour (index 0 = lundi)
 */
function getDateFromDayIndex(index) {
    // Cette fonction devrait idéalement récupérer la date depuis les données du calendrier
    // Pour l'instant, on utilise une approximation
    const today = new Date();
    const monday = new Date(today);
    monday.setDate(today.getDate() - today.getDay() + 1);
    monday.setDate(monday.getDate() + index);
    
    return monday.toISOString().split('T')[0];
}

/**
 * Convertit une heure (HH:MM) en minutes
 */
function parseTimeToMinutes(timeString) {
    const [hours, minutes] = timeString.split(':').map(Number);
    return hours * 60 + minutes;
}

/**
 * Fonction appelée lors du clic sur un créneau
 * (définie globalement pour être accessible depuis l'attribut onclick)
 */
function openReservationModal(slotElement) {
    const slotData = slotElement.getAttribute('data-slot');
    
    if (!slotData) {
        console.error('Aucune donnée de créneau trouvée');
        return;
    }
    
    try {
        const slot = JSON.parse(slotData);
        
        // Déclencher l'événement personnalisé pour le module de réservation
        const event = new CustomEvent('openReservation', {
            detail: slot
        });
        
        document.dispatchEvent(event);
        
    } catch (error) {
        console.error('Erreur lors du parsing des données du créneau:', error);
        showMessage('Erreur lors de l\'ouverture du formulaire de réservation', 'error');
    }
}

/**
 * Affiche un message à l'utilisateur
 */
function showMessage(message, type = 'info', duration = 5000) {
    const container = document.getElementById('message-container');
    if (!container) return;
    
    const messageElement = document.createElement('div');
    messageElement.className = `message message-${type} fade-in`;
    messageElement.textContent = message;
    
    // Bouton de fermeture
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.className = 'close';
    closeBtn.style.cssText = 'background: none; border: none; font-size: 20px; cursor: pointer; margin-left: 10px;';
    closeBtn.onclick = () => messageElement.remove();
    
    messageElement.appendChild(closeBtn);
    container.appendChild(messageElement);
    
    // Suppression automatique
    setTimeout(() => {
        if (messageElement.parentNode) {
            messageElement.style.opacity = '0';
            setTimeout(() => messageElement.remove(), 300);
        }
    }, duration);
}

/**
 * Recharge le calendrier (utile après une réservation)
 */
function reloadCalendar() {
    window.location.reload();
}

/**
 * Navigue vers une semaine spécifique
 */
function navigateToWeek(week) {
    const url = new URL(window.location);
    url.searchParams.set('week', week);
    window.location.href = url.toString();
}

// Styles CSS additionnels pour les créneaux passés
const pastSlotStyles = `
    .past-slot {
        opacity: 0.5;
        cursor: not-allowed !important;
        background-color: var(--color-gray) !important;
        color: var(--color-dark-gray) !important;
        border-color: var(--color-dark-gray) !important;
    }
    
    .past-slot:hover {
        transform: none !important;
        box-shadow: none !important;
    }
`;

// Injection des styles
if (!document.getElementById('calendar-dynamic-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'calendar-dynamic-styles';
    styleSheet.textContent = pastSlotStyles;
    document.head.appendChild(styleSheet);
}