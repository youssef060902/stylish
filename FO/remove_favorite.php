<?php
session_start();
header('Content-Type: application/json');

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

$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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