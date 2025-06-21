<?php
session_start();
header('Content-Type: application/json');

// Réponse par défaut
$response = ['success' => false, 'message' => 'Une erreur est survenue.'];

// Vérification de l'authentification et des données POST
if (!isset($_SESSION['admin_id'])) {
    $response['message'] = 'Accès non autorisé.';
    echo json_encode($response);
    exit();
}

if (!isset($_POST['id']) || !isset($_POST['statut'])) {
    $response['message'] = 'Données manquantes.';
    echo json_encode($response);
    exit();
}

$id = $_POST['id'];
$statut = $_POST['statut'];
$allowed_statuses = ['nouveau', 'en cours', 'résolu'];

if (!in_array($statut, $allowed_statuses)) {
    $response['message'] = 'Statut non valide.';
    echo json_encode($response);
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

    // Préparation et exécution de la requête de mise à jour
    $stmt = $pdo->prepare("UPDATE reclamation SET statut = :statut, date_modification = NOW() WHERE id = :id");
    $stmt->execute(['statut' => $statut, 'id' => $id]);

    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Statut mis à jour avec succès.';
    } else {
        $response['message'] = 'Aucune modification effectuée ou réclamation non trouvée.';
    }

} catch(PDOException $e) {
    $response['message'] = 'Erreur de base de données : ' . $e->getMessage();
}

echo json_encode($response);
?> 