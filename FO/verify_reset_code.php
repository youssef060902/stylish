<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si on reçoit un code, on vérifie
    if (isset($_POST['code'])) {
        $code = $_POST['code'];
        
        // Vérifier si le code correspond
        if (!isset($_SESSION['reset_code']) || $code != $_SESSION['reset_code']) {
            echo json_encode(['success' => false, 'message' => 'Code incorrect']);
            exit;
        }
        
        echo json_encode(['success' => true, 'message' => 'Code vérifié avec succès']);
    }
    // Si on reçoit un nouveau mot de passe, on met à jour
    else if (isset($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
        
        // Vérifier la longueur du mot de passe
        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères']);
            exit;
        }
        
        // Vérifier si le code a été vérifié
        if (!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_email'])) {
            echo json_encode(['success' => false, 'message' => 'Session expirée']);
            exit;
        }
        
        // Mettre à jour le mot de passe
        $conn = new mysqli("localhost", "root", "", "stylish");
        $stmt = $conn->prepare("UPDATE user SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $new_password, $_SESSION['reset_email']);
        
        if ($stmt->execute()) {
            // Récupérer les informations de l'utilisateur
            $stmt = $conn->prepare("SELECT id, prenom, nom, email, genre, date_naissance, phone, adresse, image FROM user WHERE email = ?");
            $stmt->bind_param("s", $_SESSION['reset_email']);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            // Créer la session utilisateur
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_genre'] = $user['genre'];
            $_SESSION['user_date_naissance'] = $user['date_naissance'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_adresse'] = $user['adresse'];
            $_SESSION['user_image'] = $user['image'];
            
            // Nettoyer la session de réinitialisation
            unset($_SESSION['reset_code']);
            unset($_SESSION['reset_email']);
            
            echo json_encode(['success' => true, 'message' => 'Mot de passe mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe']);
        }
    }
}
?> 