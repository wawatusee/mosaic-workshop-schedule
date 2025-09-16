/**
 * Reservation.js - Gestion du système de réservation
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeReservationSystem();
});

/**
 * Initialise le système de réservation
 */
function initializeReservationSystem() {
    const modal = document.getElementById('reservation-modal');
    const form = document.getElementById('reservation-form');
    const closeBtn = modal.querySelector('.close');
    const cancelBtn = modal.querySelector('.btn-cancel');
    
    // Écouter l'événement d'ouverture de réservation
    document.addEventListener('openReservation', handleReservationOpen);
    
    // Fermeture de la modal
    closeBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    
    // Fermeture en cliquant à l'extérieur
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Fermeture avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeModal();
        }
    });
    
    // Soumission du formulaire
    form.addEventListener('submit', handleFormSubmit);
    
    console.log('Système de réservation initialisé');
}

/**
 * Gère l'ouverture de la modal de réservation
 */
function handleReservationOpen(event) {
    const slotData = event.detail;
    const modal = document.getElementById('reservation-modal');
    const slotInfo = document.getElementById('selected-slot-info');
    const hiddenInput = document.getElementById('slot-info');
    
    // Formatage des informations du créneau
    const dayNames = {
        'monday': 'Lundi',
        'tuesday': 'Mardi',
        'wednesday': 'Mercredi',
        'thursday': 'Jeudi',
        'friday': 'Vendredi',
        'saturday': 'Samedi',
        'sunday': 'Dimanche'
    };
    
    const endTime = calculateEndTime(slotData.time, slotData.duration);
    const dayName = dayNames[slotData.day] || slotData.day;
    
    slotInfo.innerHTML = `
        <strong>Créneau sélectionné :</strong><br>
        ${dayName} de ${slotData.time} à ${endTime} (${slotData.duration}h)
    `;
    
    // Stocker les données du créneau
    hiddenInput.value = JSON.stringify(slotData);
    
    // Réinitialiser le formulaire
    document.getElementById('reservation-form').reset();
    
    // Ouvrir la modal
    openModal();
}

/**
 * Calcule l'heure de fin d'un créneau
 */
function calculateEndTime(startTime, duration) {
    const [hours, minutes] = startTime.split(':').map(Number);
    const endDate = new Date();
    endDate.setHours(hours, minutes);
    endDate.setHours(endDate.getHours() + duration);
    
    return endDate.toTimeString().substring(0, 5);
}

/**
 * Ouvre la modal
 */
function openModal() {
    const modal = document.getElementById('reservation-modal');
    modal.classList.add('active');
    
    // Focus sur le premier champ
    const firstInput = modal.querySelector('input[type="text"], input[type="email"]');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
    
    // Empêcher le scroll du body
    document.body.style.overflow = 'hidden';
}

/**
 * Ferme la modal
 */
function closeModal() {
    const modal = document.getElementById('reservation-modal');
    modal.classList.remove('active');
    
    // Restaurer le scroll du body
    document.body.style.overflow = '';
}

/**
 * Gère la soumission du formulaire
 */
async function handleFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = form.querySelector('.btn-submit');
    const originalText = submitBtn.textContent;
    
    // Désactiver le bouton et afficher le loading
    submitBtn.disabled = true;
    submitBtn.textContent = 'Réservation...';
    
    try {
        // Récupération des données du formulaire
        const formData = new FormData(form);
        const slotData = JSON.parse(formData.get('slot-info'));
        
        const reservationData = {
            slot: slotData,
            client: {
                name: formData.get('client-name'),
                email: formData.get('client-email'),
                phone: formData.get('client-phone') || '',
                message: formData.get('message') || ''
            }
        };
        
        // Validation côté client
        if (!validateReservationData(reservationData)) {
            return;
        }
        
        // Envoi de la réservation
        const response = await fetch('api/make-reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(reservationData)
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Réservation réussie
            showMessage('Réservation envoyée avec succès ! Vous recevrez une confirmation par email.', 'success');
            closeModal();
            
            // Recharger le calendrier après un délai
            setTimeout(() => {
                reloadCalendar();
            }, 2000);
            
        } else {
            // Erreur de réservation
            const errorMessage = result.message || 'Erreur lors de la réservation';
            showMessage(errorMessage, 'error');
        }
        
    } catch (error) {
        console.error('Erreur lors de la réservation:', error);
        showMessage('Erreur de connexion. Veuillez réessayer.', 'error');
        
    } finally {
        // Réactiver le bouton
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

/**
 * Valide les données de réservation côté client
 */
function validateReservationData(data) {
    const { client } = data;
    
    // Validation du nom
    if (!client.name || client.name.trim().length < 2) {
        showMessage('Veuillez saisir un nom valide', 'error');
        document.getElementById('client-name').focus();
        return false;
    }
    
    // Validation de l'email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!client.email || !emailRegex.test(client.email)) {
        showMessage('Veuillez saisir un email valide', 'error');
        document.getElementById('client-email').focus();
        return false;
    }
    
    // Validation du téléphone (optionnel mais si présent, doit être valide)
    if (client.phone && client.phone.trim()) {
        const phoneRegex = /^[\d\s\-\+\(\)\.]{8,}$/;
        if (!phoneRegex.test(client.phone.trim())) {
            showMessage('Veuillez saisir un numéro de téléphone valide', 'error');
            document.getElementById('client-phone').focus();
            return false;
        }
    }
    
    return true;
}

/**
 * Amélioration de l'UX du formulaire
 */
document.addEventListener('DOMContentLoaded', function() {
    // Auto-formatage du téléphone
    const phoneInput = document.getElementById('client-phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Supprimer tous les non-chiffres
            
            // Format français automatique
            if (value.startsWith('33')) {
                value = '+33 ' + value.substring(2);
            } else if (value.startsWith('0')) {
                value = value.replace(/^0/, '+33 ');
            }
            
            // Formatage avec espaces
            value = value.replace(/(\+33\s?)(\d{1})(\d{2})(\d{2})(\d{2})(\d{2})/, '$1$2 $3 $4 $5 $6');
            
            e.target.value = value;
        });
    }
    
    // Validation en temps réel de l'email
    const emailInput = document.getElementById('client-email');
    if (emailInput) {
        emailInput.addEventListener('blur', function(e) {
            const email = e.target.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                e.target.style.borderColor = 'var(--color-danger-dark)';
            } else {
                e.target.style.borderColor = '';
            }
        });
    }
    
    // Auto-capitalisation du nom
    const nameInput = document.getElementById('client-name');
    if (nameInput) {
        nameInput.addEventListener('input', function(e) {
            const words = e.target.value.split(' ');
            const capitalizedWords = words.map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            );
            e.target.value = capitalizedWords.join(' ');
        });
    }
});

/**
 * Fonction utilitaire pour afficher les messages
 * (utilise la fonction du fichier calendar.js)
 */
function showMessage(message, type = 'info', duration = 5000) {
    // Cette fonction est définie dans calendar.js
    if (typeof window.showMessage === 'function') {
        window.showMessage(message, type, duration);
    } else {
        // Fallback simple
        alert(message);
    }
}