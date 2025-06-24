<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}

if (!isset($_POST['produit_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID produit manquant.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$produit_id = intval($_POST['produit_id']);

try {
    $stmt = $pdo->prepare("DELETE FROM favoris WHERE id_user = ? AND id_produit = ?");
    $stmt->execute([$user_id, $produit_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Favori non trouvé ou déjà supprimé.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
} 