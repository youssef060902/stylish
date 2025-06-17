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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupération et validation de l'ID
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID de promotion invalide']);
    exit();
}

try {
    // Récupérer les produits associés à la promotion
    $stmt = $pdo->prepare("SELECT id, prix, id_promotion FROM produit WHERE id_promotion = ?");
    $stmt->execute([$id]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque produit, restaurer le prix initial et mettre à jour le statut
    foreach ($produits as $produit) {
        // Récupérer le pourcentage de réduction de la promotion
        $stmt_promo = $pdo->prepare("SELECT discount FROM promotion WHERE id = ?");
        $stmt_promo->execute([$id]);
        $promotion = $stmt_promo->fetch(PDO::FETCH_ASSOC);
        
        if ($promotion) {
            // Calculer le prix initial : prix_initial = prix_actuel / (1 - discount/100)
            $prix_initial = $produit['prix'] / (1 - $promotion['discount']/100);
            $prix_initial = round($prix_initial, 2);

            // Mettre à jour le produit avec le prix initial et le nouveau statut
            $stmt_update = $pdo->prepare("UPDATE produit SET prix = ?, id_promotion = NULL, statut = 'en stock' WHERE id = ?");
            $stmt_update->execute([$prix_initial, $produit['id']]);
        }
    }

    // Suppression de la promotion
    $stmt = $pdo->prepare("DELETE FROM promotion WHERE id = ?");
    $stmt->execute([$id]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Promotion supprimée avec succès et prix des produits restaurés']);

} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression de la promotion']);
} 