<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : null;

if (!$id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE coupon SET statut = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
} 