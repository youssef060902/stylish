<?php
require_once __DIR__ . '/../config/database.php';
// Désactiver l'affichage des erreurs
error_reporting(0);
ini_set('display_errors', 0);

// get_product_details.php
header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Erreur de connexion : " . $e->getMessage()]);
    exit();
}

if (isset($_GET['id'])) {
    $productId = intval($_GET['id']);

    // Récupérer les détails du produit
    try {
        $stmt = $pdo->prepare("SELECT p.*, pr.discount, pr.nom as promotion_nom
                               FROM produit p 
                               LEFT JOIN promotion pr ON p.id_promotion = pr.id 
                               WHERE p.id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Récupérer toutes les images du produit
            $stmt_images = $pdo->prepare("SELECT URL_Image FROM images_produits WHERE id_produit = ? ORDER BY id ASC");
            $stmt_images->execute([$productId]);
            $images = $stmt_images->fetchAll(PDO::FETCH_COLUMN);
            $product['images'] = $images;

            // Récupérer les pointures et stocks associés
            $stmt_pointures = $pdo->prepare("SELECT DISTINCT pt.pointure
                                             FROM pointure_produit pp
                                             JOIN pointures pt ON pp.id_pointure = pt.id
                                             WHERE pp.id_produit = ? AND pp.stock > 0
                                             ORDER BY pt.pointure ASC");
            $stmt_pointures->execute([$productId]);
            $pointures = $stmt_pointures->fetchAll(PDO::FETCH_COLUMN);
            $product['pointures'] = $pointures;

            // Déterminer le statut du produit
            $product['statut'] = $product['statut'] ?? 'en stock';
            if ($product['discount'] > 0) {
                $product['statut'] = 'en promotion';
            }

            // Formater les données pour correspondre à ce qui est attendu par le JavaScript
            $formattedProduct = [
                'nom' => $product['nom'],
                'marque' => $product['marque'],
                'catégorie' => ucfirst($product['catégorie']), // Capitaliser la première lettre
                'type' => ucfirst($product['type']), // Capitaliser la première lettre
                'couleur' => $product['couleur'],
                'description' => $product['description'],
                'prix' => $product['prix'],
                'discount' => $product['discount'],
                'statut' => $product['statut'],
                'images' => $product['images'],
                'pointures' => $product['pointures']
            ];

            echo json_encode(['success' => true, 'data' => $formattedProduct]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Produit non trouvé.']);
        }

    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur de récupération du produit : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID de produit manquant.']);
}
?>