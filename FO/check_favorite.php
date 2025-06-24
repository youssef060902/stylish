<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'ID produit invalide.';
    echo json_encode($response);
    exit;
}

$productId = (int)$_GET['id'];
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    // Si l'utilisateur n'est pas connecté, le produit ne peut pas être dans ses favoris
    $response['success'] = true;
    $response['isFavorite'] = false;
    echo json_encode($response);
    exit;
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    $response['message'] = 'Erreur de connexion à la base de données.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM favoris WHERE id_user = ? AND id_produit = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $response['success'] = true;
    $response['isFavorite'] = ($count > 0);

} catch (Exception $e) {
    error_log("Error checking favorite status: " . $e->getMessage());
    $response['message'] = 'Erreur lors de la vérification du statut favori.';
}

$conn->close();
echo json_encode($response);
?> 