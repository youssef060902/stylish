<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour supprimer votre compte.']);
    exit;
}

$user_id = $_SESSION['user_id'];



try {
    // Préparer et exécuter la requête de suppression
    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Erreur lors de la préparation de la requête.");
    }

    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Suppression réussie, détruire la session
        session_unset();
        session_destroy();

        // Renvoyer une réponse JSON avec l'URL de redirection
        echo json_encode([
            'success' => true,
            'message' => 'Votre compte a été supprimé avec succès.',
            'redirect' => true,
            'url' => $_SERVER['HTTP_REFERER'] ?? 'index.php'
        ]);
    } else {
        throw new Exception("Erreur lors de la suppression du compte.");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Fermer la connexion une seule fois à la fin
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 