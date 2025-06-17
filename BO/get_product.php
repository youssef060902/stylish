<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérification de l'ID du produit
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
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
    // Récupération des informations du produit
    $stmt = $pdo->prepare("
        SELECT p.*,
               SUM(pp.stock) as total_quantite_pointures
        FROM produit p
        LEFT JOIN pointure_produit pp ON p.id = pp.id_produit
        LEFT JOIN pointures pt ON pp.id_pointure = pt.id
        WHERE p.id = :id
        GROUP BY p.id
    ");
    $stmt->execute([':id' => $_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        exit();
    }

    // Récupération des images du produit (nouvelle requête séparée)
    $stmt_images = $pdo->prepare("SELECT id, URL_Image FROM images_produits WHERE id_produit = :id_produit");
    $stmt_images->execute([':id_produit' => $product['id']]);
    $images_data = $stmt_images->fetchAll(PDO::FETCH_ASSOC);

    $product['images'] = [];
    $product['image_ids'] = [];
    foreach ($images_data as $img) {
        $product['images'][] = $img['URL_Image'];
        $product['image_ids'][] = $img['id'];
    }

    // Récupération et formatage des pointures
    $stmt_pointures = $pdo->prepare("SELECT pt.id, pt.pointure, pp.stock FROM pointure_produit pp JOIN pointures pt ON pp.id_pointure = pt.id WHERE pp.id_produit = :id_produit");
    $stmt_pointures->execute([':id_produit' => $product['id']]);
    $product['pointures_stocks'] = $stmt_pointures->fetchAll(PDO::FETCH_ASSOC);

    // Mettre à jour la quantité totale du produit avec la somme des stocks de pointures
    if (isset($product['total_quantite_pointures'])) {
        $product['quantité'] = intval($product['total_quantite_pointures']);
    } else {
        $product['quantité'] = 0;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $product]);

} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du produit: ' . $e->getMessage()]);
}
?> 