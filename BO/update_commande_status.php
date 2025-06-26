<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$statut = isset($_POST['statut']) ? trim($_POST['statut']) : '';

$statuts_valides = ['en attente', 'confirmé', 'en préparation', 'expédié', 'livré'];
if ($id <= 0 || !in_array($statut, $statuts_valides)) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']);
    exit;
}

$stmt = $pdo->prepare('UPDATE commande SET statut = ? WHERE id = ?');
$ok = $stmt->execute([$statut, $id]);

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour.']);
} 