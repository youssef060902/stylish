<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    echo json_encode(['message' => 'ID invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, code, discount FROM coupon WHERE id = ?");
    $stmt->execute([$id]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        echo json_encode($coupon);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Coupon non trouvé']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Erreur serveur']);
} 