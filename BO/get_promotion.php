<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Connexion à la base de données
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Vérification des données reçues
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de promotion non spécifié']);
    exit();
}

// Récupération et validation de l'ID
$id = intval($_GET['id']);

if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de promotion invalide']);
    exit();
}

try {
    // Récupération des détails de la promotion
    $stmt = $pdo->prepare("SELECT * FROM promotion WHERE id = ?");
    $stmt->execute([$id]);
    $promotion = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($promotion) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $promotion]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Promotion non trouvée']);
    }

} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des détails de la promotion']);
} 