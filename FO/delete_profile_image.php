<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour modifier votre profil.']);
    exit;
}

try {
    $mysqli = new mysqli("localhost", "root", "", "stylish");

    if ($mysqli->connect_error) {
        throw new Exception("Erreur de connexion : " . $mysqli->connect_error);
    }

    $user_id = $_SESSION['user_id'];
    $old_image = $_SESSION['user_image'];

    // Supprimer l'ancienne image si elle existe
    if ($old_image && file_exists($old_image)) {
        unlink($old_image);
    }

    // Mettre à jour la base de données
    $stmt = $mysqli->prepare("UPDATE user SET image = NULL WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        // Mettre à jour la session
        $_SESSION['user_image'] = null;
        echo json_encode(['success' => true, 'message' => 'Photo de profil supprimée avec succès.']);
    } else {
        throw new Exception("Erreur lors de la suppression de la photo de profil.");
    }

    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
}
?> 