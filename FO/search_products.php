<?php
header('Content-Type: application/json');

// Configuration de la base de données
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de connexion à la base de données']);
    exit;
}

$query = isset($_GET['q']) ? $_GET['q'] : '';
$search = "%{$query}%";

try {
    $sql = "SELECT p.id, p.nom, p.prix, p.catégorie, i.URL_Image 
            FROM produit p 
            LEFT JOIN images_produits i ON p.id = i.id_produit";
    
    if (!empty($query)) {
        $sql .= " WHERE p.nom LIKE :search OR p.description LIKE :search";
    }
    
    $sql .= " GROUP BY p.id LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($query)) {
        $stmt->bindParam(':search', $search);
    }
    
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les résultats
    $formatted_results = array_map(function($product) {
        return [
            'id' => $product['id'],
            'name' => htmlspecialchars($product['nom']),
            'price' => number_format($product['prix'], 2),
            'category' => $product['catégorie'],
            'image' => $product['URL_Image'] ? $product['URL_Image'] : 'images/default-product.jpg'
        ];
    }, $results);
    
    echo json_encode($formatted_results);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la recherche']);
}
?> 