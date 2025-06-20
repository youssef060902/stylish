document.addEventListener('DOMContentLoaded', function() {
    const BASE_PHP_PATH = '/stylish/FO/'; // Chemin absolu vers les scripts PHP

    const forgotPasswordForm = document.getElementById('forgot-password-form');
    const verifyCodeForm = document.getElementById('verify-code-form');
    const newPasswordForm = document.getElementById('new-password-form');
    const resendCodeBtn = document.getElementById('resend-code-btn');
    const toast = new bootstrap.Toast(document.getElementById('toast'));
    let countdownInterval;

    // Fonction pour démarrer le chronomètre
    function startCountdown() {
        // Arrêter le chronomètre existant s'il y en a un
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        let timeLeft = 60;
        resendCodeBtn.disabled = true;
        resendCodeBtn.classList.add('disabled');
        
        function updateButton() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            const displayTime = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            resendCodeBtn.innerHTML = `Renvoyer dans ${displayTime}`;
        }

        updateButton(); // Mise à jour initiale

        countdownInterval = setInterval(() => {
            timeLeft--;
            updateButton();

            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                resendCodeBtn.disabled = false;
                resendCodeBtn.classList.remove('disabled');
                resendCodeBtn.innerHTML = 'Renvoyer';
            }
        }, 1000);
    }

    // Fonction pour renvoyer le code
    async function resendCode() {
        const email = sessionStorage.getItem('reset_email');
        if (!email) {
            showToast('Erreur', 'Session expirée. Veuillez recommencer le processus.', false);
            return;
        }

        try {
            resendCodeBtn.disabled = true;
            resendCodeBtn.classList.add('disabled');
            resendCodeBtn.innerHTML = 'Envoi en cours...';

            const response = await fetch(BASE_PHP_PATH + 'forgot_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `email=${encodeURIComponent(email)}`
            });

            const data = await response.json();
            
            if (data.success) {
                startCountdown();
                showToast('Succès', 'Code renvoyé avec succès !', true);
            } else {
                resendCodeBtn.disabled = false;
                resendCodeBtn.classList.remove('disabled');
                resendCodeBtn.innerHTML = 'Renvoyer';
                showToast('Erreur', data.message, false);
            }
        } catch (error) {
            console.error('Erreur:', error);
            resendCodeBtn.disabled = false;
            resendCodeBtn.classList.remove('disabled');
            resendCodeBtn.innerHTML = 'Renvoyer';
            showToast('Erreur', 'Une erreur est survenue lors de l\'envoi du code', false);
        }
    }

    // Fonction pour afficher les notifications
    function showToast(title, message, isSuccess = true) {
        Swal.fire({
            title: title,
            text: message,
            icon: isSuccess ? 'success' : 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
        });
    }

    // Gestion du formulaire d'email
    forgotPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = document.getElementById('reset_email').value;
        sessionStorage.setItem('reset_email', email);

        fetch(BASE_PHP_PATH + 'forgot_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentModal = bootstrap.Modal.getInstance(document.getElementById('modalForgotPassword'));
                currentModal.hide();
                const verificationModal = new bootstrap.Modal(document.getElementById('modalVerificationCode'));
                verificationModal.show();
                startCountdown();
                showToast('Succès', 'Code envoyé avec succès !', true);
            } else {
                showToast('Erreur', data.message, false);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur', 'Une erreur est survenue', false);
        });
    });

    // Gestion du formulaire de vérification
    verifyCodeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const code = document.getElementById('verification_code').value;

        fetch(BASE_PHP_PATH + 'verify_reset_code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `code=${encodeURIComponent(code)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentModal = bootstrap.Modal.getInstance(document.getElementById('modalVerificationCode'));
                currentModal.hide();
                const newPasswordModal = new bootstrap.Modal(document.getElementById('modalNewPassword'));
                newPasswordModal.show();
                showToast('Succès', 'Code vérifié avec succès !', true);
            } else {
                showToast('Erreur', data.message, false);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur', 'Une erreur est survenue', false);
        });
    });

    // Gestion du formulaire de nouveau mot de passe
    newPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const newPassword = document.getElementById('new_password').value;

        if (newPassword.length < 6) {
            showToast('Erreur', 'Le mot de passe doit contenir au moins 6 caractères', false);
            return;
        }

        fetch(BASE_PHP_PATH + 'verify_reset_code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `new_password=${encodeURIComponent(newPassword)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentModal = bootstrap.Modal.getInstance(document.getElementById('modalNewPassword'));
                currentModal.hide();
                forgotPasswordForm.reset();
                verifyCodeForm.reset();
                newPasswordForm.reset();
                Swal.fire({
                    title: 'Succès!',
                    text: 'Mot de passe réinitialisé avec succès !',
                    icon: 'success',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
                window.location.href = 'http://localhost/stylish/FO/index.php';
            } else {
                showToast('Erreur', data.message, false);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur', 'Une erreur est survenue', false);
        });
    });

    // Ajouter l'événement de clic sur le bouton de renvoi
    resendCodeBtn.addEventListener('click', resendCode);

    // Démarrer le chronomètre au chargement de la page
    startCountdown();
});