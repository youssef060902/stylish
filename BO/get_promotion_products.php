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
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Vérification des données reçues
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de promotion non spécifié']);
    exit();
}

// Récupération et validation de l'ID
$promotionId = intval($_GET['id']);

if ($promotionId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de promotion invalide']);
    exit();
}

try {
    // Récupérer les produits associés à la promotion
    $stmt = $pdo->prepare("
        SELECT 
            p.id, 
            p.nom, 
            p.marque, 
            p.prix as prix_actuel, 
            p.statut,
            (SELECT URL_Image FROM images_produits WHERE id_produit = p.id ORDER BY id ASC LIMIT 1) as image,
            pr.discount
        FROM 
            produit p
        LEFT JOIN 
            promotion pr ON p.id_promotion = pr.id
        WHERE 
            p.id_promotion = ?
    ");
    $stmt->execute([$promotionId]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculer le prix initial et le prix réduit pour chaque produit
    foreach ($products as &$product) {
        $product['prix_initial'] = $product['prix_actuel']; // Par défaut, le prix initial est le prix actuel

        if ($product['discount'] !== null && $product['discount'] > 0) {
            // Si le produit est en promotion, calculer le prix initial avant réduction
            $product['prix_initial'] = round($product['prix_actuel'] / (1 - $product['discount'] / 100), 2);
            $product['prix_reduit'] = $product['prix_actuel'];
        } else {
            // Si pas de promotion, le prix réduit est le même que le prix initial
            $product['prix_reduit'] = $product['prix_actuel'];
        }
        unset($product['prix_actuel']); // Supprimer la colonne prix_actuel
        unset($product['discount']);    // Supprimer la colonne discount
    }
    unset($product); // Rompre la référence sur le dernier élément

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'products' => $products]);

} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des produits de la promotion: ' . $e->getMessage()]);
}
?> 