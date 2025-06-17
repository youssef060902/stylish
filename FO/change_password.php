<?php
session_start();
header('Content-Type: application/json');
// Configuration de la base de données
$host = "localhost";
$user = "root";
$pass = "";
$db = "stylish";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données.'
    ]);
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vous devez être connecté pour changer votre mot de passe.'
    ]);
    exit;
}

// Vérifier si les données nécessaires sont présentes
if (!isset($_POST['current_password']) || !isset($_POST['new_password']) || !isset($_POST['confirm_new_password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Tous les champs sont requis.'
    ]);
    exit;
}

$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_new_password = $_POST['confirm_new_password'];
$user_id = $_SESSION['user_id'];

// Vérifier si le nouveau mot de passe et la confirmation correspondent
if ($new_password !== $confirm_new_password) {
    echo json_encode([
        'success' => false,
        'message' => 'Le nouveau mot de passe et la confirmation ne correspondent pas.'
    ]);
    exit;
}

try {
    // Récupérer le mot de passe actuel de l'utilisateur
    $stmt = $conn->prepare("SELECT password FROM user WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Utilisateur non trouvé.'
        ]);
        exit;
    }

    // Vérifier si le mot de passe actuel est correct (comparaison directe)
    if ($current_password !== $user['password']) {
        echo json_encode([
            'success' => false,
            'message' => 'Le mot de passe actuel est incorrect.'
        ]);
        exit;
    }

    // Mettre à jour le mot de passe (en clair)
    $stmt = $conn->prepare("UPDATE user SET password = ? WHERE id = ?");
    $stmt->execute([$new_password, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Votre mot de passe a été changé avec succès.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors du changement de mot de passe.'
    ]);
}
?> 