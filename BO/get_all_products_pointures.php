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

    // Récupérer tous les produits avec leurs pointures
    $stmt = $pdo->query("
        SELECT p.id, pp.id_pointure, pp.stock
        FROM produit p
        LEFT JOIN pointure_produit pp ON p.id = pp.id_produit
        ORDER BY p.id
    ");
    
    $products = [];
    $currentProduct = null;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($currentProduct === null || $currentProduct['id'] !== $row['id']) {
            if ($currentProduct !== null) {
                $products[] = $currentProduct;
            }
            $currentProduct = [
                'id' => $row['id'],
                'pointures_stocks' => []
            ];
        }
        if ($row['id_pointure'] !== null) {
            $currentProduct['pointures_stocks'][] = [
                'id' => $row['id_pointure'],
                'stock' => $row['stock']
            ];
        }
    }
    
    if ($currentProduct !== null) {
        $products[] = $currentProduct;
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'products' => $products]);

} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 