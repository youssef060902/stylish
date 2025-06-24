<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$user_id = $_SESSION['user_id'];
$id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM reclamation WHERE id = ? AND id_user = ?");
    $stmt->execute([$id, $user_id]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rec) {
        echo json_encode([
            'success' => true,
            'data' => $rec
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Réclamation introuvable']);
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
} 