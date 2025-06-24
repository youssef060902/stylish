<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: reclamations.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $user_id = $_SESSION['user_id'];
    $id = $_POST['id'];
    try {
        // La connexion $pdo est déjà disponible
        // Vérifier que la réclamation appartient à l'utilisateur et est supprimable
        $stmt = $pdo->prepare("SELECT * FROM reclamation WHERE id = ? AND id_user = ? AND statut IN ('nouveau', 'en cours')");
        $stmt->execute([$id, $user_id]);
        $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reclamation) {
            $stmt = $pdo->prepare("DELETE FROM reclamation WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success_message'] = "Réclamation supprimée avec succès.";
        } else {
            $_SESSION['error_message'] = "Suppression non autorisée.";
        }
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la suppression.";
    }
}
header('Location: reclamations.php');
exit(); 