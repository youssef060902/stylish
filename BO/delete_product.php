<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérification de l'ID du produit
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de produit invalide']);
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

try {
    // Récupération des images du produit
    $stmt = $pdo->prepare("SELECT URL_Image FROM images_produits WHERE id_produit = :id");
    $stmt->execute([':id' => $_POST['id']]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Suppression des images physiques
    foreach ($images as $image) {
        $filepath = str_replace('http://localhost/', $_SERVER['DOCUMENT_ROOT'] . '/', $image);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    // Suppression des enregistrements dans la base de données
    $pdo->beginTransaction();

    // Suppression des images
    $stmt = $pdo->prepare("DELETE FROM images_produits WHERE id_produit = :id");
    $stmt->execute([':id' => $_POST['id']]);

    // Suppression du produit
    $stmt = $pdo->prepare("DELETE FROM produit WHERE id = :id");
    $stmt->execute([':id' => $_POST['id']]);

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Produit supprimé avec succès']);

} catch(PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression du produit: ' . $e->getMessage()]);
}
?> 