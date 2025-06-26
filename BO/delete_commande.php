<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de commande invalide.']);
    exit;
}

// Supprimer d'abord les produits associés à la commande
$pdo->prepare('DELETE FROM commande_produit WHERE id_commande = ?')->execute([$id]);
// Puis la commande elle-même
$deleted = $pdo->prepare('DELETE FROM commande WHERE id = ?')->execute([$id]);

if ($deleted) {
    echo json_encode(['success' => true, 'message' => 'Commande supprimée avec succès.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression.']);
} 