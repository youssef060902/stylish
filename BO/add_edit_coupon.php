<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$code = trim($_POST['code'] ?? '');
$discount = filter_var($_POST['discount'] ?? 0, FILTER_VALIDATE_INT);

if (empty($code) || $discount === false || $discount < 1 || $discount > 100) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

try {
    if (empty($id)) { // Ajout
        $stmt = $pdo->prepare("INSERT INTO coupon (code, discount, statut) VALUES (?, ?, 'inactive')");
        $stmt->execute([$code, $discount]);
        $message = 'Coupon ajouté avec succès.';
    } else { // Modification
        $stmt = $pdo->prepare("UPDATE coupon SET code = ?, discount = ? WHERE id = ?");
        $stmt->execute([$code, $discount, $id]);
        $message = 'Coupon mis à jour avec succès.';
    }
    echo json_encode(['success' => true, 'message' => $message]);
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Duplicate entry
        echo json_encode(['success' => false, 'message' => 'Ce code de coupon existe déjà.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données.']);
    }
} 