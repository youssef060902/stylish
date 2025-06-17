<?php
require_once __DIR__ . '/../config/database.php';

// Récupérer les filtres
$categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$marque = isset($_GET['marque']) ? $_GET['marque'] : '';
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$prix_min = isset($_GET['prix_min']) && $_GET['prix_min'] !== '' ? floatval($_GET['prix_min']) : 0;
$prix_max = isset($_GET['prix_max']) && $_GET['prix_max'] !== '' ? floatval($_GET['prix_max']) : PHP_FLOAT_MAX;
$pointure_id = isset($_GET['pointure']) && $_GET['pointure'] !== '' ? intval($_GET['pointure']) : 0;

// Construire la requête SQL
$sql = "SELECT p.*, 
        GROUP_CONCAT(DISTINCT pt.pointure ORDER BY pt.pointure ASC) as pointures_disponibles,
        pr.discount as promotion_discount,
        pr.date_debut as promotion_debut,
        pr.date_fin as promotion_fin,
        ip.URL_Image as image_url 
        FROM produit p 
        LEFT JOIN pointure_produit pp ON p.id = pp.id_produit
        LEFT JOIN pointures pt ON pp.id_pointure = pt.id
        LEFT JOIN promotion pr ON p.id_promotion = pr.id
        LEFT JOIN images_produits ip ON p.id = ip.id_produit
        WHERE 1=1";

$params = [];
$types = '';

if ($categorie !== '') {
    $sql .= " AND p.catégorie = ?";
    $params[] = $categorie;
    $types .= 's';
}

if ($type !== '') {
    $sql .= " AND p.type = ?";
    $params[] = $type;
    $types .= 's';
}

if ($marque !== '') {
    $sql .= " AND p.marque = ?";
    $params[] = $marque;
    $types .= 's';
}

if ($statut !== '') {
    $sql .= " AND p.statut = ?";
    $params[] = $statut;
    $types .= 's';
}

if ($prix_min > 0) {
    $sql .= " AND p.prix >= ?";
    $params[] = $prix_min;
    $types .= 'd';
}

if ($prix_max < PHP_FLOAT_MAX) {
    $sql .= " AND p.prix <= ?";
    $params[] = $prix_max;
    $types .= 'd';
}

if ($pointure_id > 0) {
    $sql .= " AND pt.id = ?";
    $params[] = $pointure_id;
    $types .= 'i';
}

$sql .= " GROUP BY p.id ORDER BY p.date_ajout DESC";

try {
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    // Formater les données pour l'affichage
    foreach ($products as &$product) {
        $product['pointures_disponibles'] = $product['pointures_disponibles'] ? explode(',', $product['pointures_disponibles']) : [];
        // Ajuster l'URL de l'image si nécessaire (si elle n'est pas absolue)
        if (!empty($product['image_url']) && strpos($product['image_url'], 'http') !== 0) {
            $product['image_url'] = 'http://localhost/img/' . basename($product['image_url']);
        }

        if ($product['id_promotion']) {
            $product['prix_promo'] = $product['prix'] * (1 - $product['promotion_discount'] / 100);
        }
    }
    
    echo json_encode(['success' => true, 'products' => $products]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 