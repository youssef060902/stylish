<?php
session_start();
header('Content-Type: application/json');

// Vérification de la connexion
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour ajouter un avis']);
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
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Vérification des données reçues
if (!isset($_POST['product_id']) || !isset($_POST['rating']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

$product_id = intval($_POST['product_id']);
$rating = intval($_POST['rating']);
$comment = trim($_POST['comment']);
$user_id = $_SESSION['user_id'];

// Validation des données
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'La note doit être comprise entre 1 et 5']);
    exit();
}

if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire ne peut pas être vide']);
    exit();
}

try {
    // Ajouter l'avis
    $stmt = $pdo->prepare("INSERT INTO avis (id_user, id_produit, note, commentaire, date_creation, date_modification) 
                          VALUES (?, ?, ?, ?, NOW(), NULL)");
    $stmt->execute([$user_id, $product_id, $rating, $comment]);

    echo json_encode(['success' => true, 'message' => 'Avis ajouté avec succès']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'avis']);
}
?> 