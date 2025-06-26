<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID invalide.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM coupon WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Coupon supprimé.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
} 