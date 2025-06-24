<?php
require_once __DIR__ . '/../config/database.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit();
}
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$user_id = $_SESSION['user_id'];
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID manquant.']);
    exit();
}
try {
    // Utiliser la connexion PDO centralisée
    $stmt = $pdo->prepare("DELETE FROM avis WHERE id = ? AND id_user = ?");
    $stmt->execute([$id, $user_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Avis supprimé avec succès.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Suppression impossible.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
} 