<?php
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Une erreur est survenue.'];

if (!isset($_SESSION['admin_id'])) {
    $response['message'] = 'Accès non autorisé.';
    echo json_encode($response);
    exit();
}

if (!isset($_POST['review_id'])) {
    $response['message'] = 'ID de l\'avis manquant.';
    echo json_encode($response);
    exit();
}

$review_id = (int)$_POST['review_id'];

// Connexion à la base de données
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("DELETE FROM avis WHERE id = ?");
    $stmt->execute([$review_id]);

    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Avis supprimé avec succès.';
    } else {
        $response['message'] = 'Avis non trouvé ou déjà supprimé.';
    }

} catch (PDOException $e) {
    $response['message'] = 'Erreur de base de données : ' . $e->getMessage();
}

echo json_encode($response);
?> 