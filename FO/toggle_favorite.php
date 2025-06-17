<?php
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Utilisateur non connecté.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
$productId = $input['productId'] ?? null;

if (!$productId || !is_numeric($productId)) {
    $response['message'] = 'ID produit invalide.';
    echo json_encode($response);
    exit;
}

$productId = (int)$productId;

$host = 'localhost';
$db = 'stylish';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    $response['message'] = 'Erreur de connexion à la base de données.';
    echo json_encode($response);
    exit;
}

try {
    // Vérifier si le produit est déjà dans les favoris
    $stmt = $conn->prepare("SELECT COUNT(*) FROM favoris WHERE id_utilisateur = ? AND id_produit = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        // Supprimer des favoris
        $stmt = $conn->prepare("DELETE FROM favoris WHERE id_utilisateur = ? AND id_produit = ?");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();
        $response['success'] = true;
        $response['isFavorite'] = false;
        $response['message'] = 'Produit retiré des favoris.';
    } else {
        // Ajouter aux favoris
        $stmt = $conn->prepare("INSERT INTO favoris (id_utilisateur, id_produit) VALUES (?, ?)");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
        $stmt->close();
        $response['success'] = true;
        $response['isFavorite'] = true;
        $response['message'] = 'Produit ajouté aux favoris.';
    }

} catch (Exception $e) {
    error_log("Error toggling favorite status: " . $e->getMessage());
    $response['message'] = 'Erreur lors de la mise à jour des favoris.';
}

$conn->close();
echo json_encode($response);
?> 