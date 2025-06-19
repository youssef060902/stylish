<?php
session_start();
header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $id_produit = intval($_POST['id']);
    $id_pointure = intval($_POST['pointure']);
    $quantite = intval($_POST['quantite']);
    // Vérifier le stock disponible
    $stmt = $pdo->prepare("SELECT stock FROM pointure_produit WHERE id_produit=? AND id_pointure=?");
    $stmt->execute([$id_produit, $id_pointure]);
    $stock = $stmt->fetchColumn();
    if ($stock === false) {
        echo json_encode(['success' => false, 'message' => 'Pointure non disponible']);
        exit;
    }
    // Quantité déjà dans le panier
    $stmt = $pdo->prepare("SELECT quantite FROM panier WHERE id_user=? AND id_produit=? AND id_pointure=?");
    $stmt->execute([$user_id, $id_produit, $id_pointure]);
    $quantite_panier = $stmt->fetchColumn();
    $quantite_totale = $quantite + ($quantite_panier ? $quantite_panier : 0);
    if ($quantite_totale > $stock) {
        echo json_encode(['success' => false, 'message' => 'Stock insuffisant pour cette pointure (max: '.$stock.')']);
        exit;
    }
    // Vérifier si déjà présent
    $stmt = $pdo->prepare("SELECT quantite FROM panier WHERE id_user=? AND id_produit=? AND id_pointure=?");
    $stmt->execute([$user_id, $id_produit, $id_pointure]);
    if ($row = $stmt->fetch()) {
        // Update
        $stmt = $pdo->prepare("UPDATE panier SET quantite = quantite + ? WHERE id_user=? AND id_produit=? AND id_pointure=?");
        $stmt->execute([$quantite, $user_id, $id_produit, $id_pointure]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO panier (id_user, id_produit, id_pointure, quantite) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $id_produit, $id_pointure, $quantite]);
    }
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update') {
    $id_produit = intval($_POST['id']);
    $id_pointure = intval($_POST['pointure']);
    $quantite = intval($_POST['quantite']);
    // Vérifier le stock disponible
    $stmt = $pdo->prepare("SELECT stock FROM pointure_produit WHERE id_produit=? AND id_pointure=?");
    $stmt->execute([$id_produit, $id_pointure]);
    $stock = $stmt->fetchColumn();
    if ($stock === false) {
        echo json_encode(['success' => false, 'message' => 'Pointure non disponible']);
        exit;
    }
    if ($quantite > $stock) {
        echo json_encode(['success' => false, 'message' => 'Stock insuffisant pour cette pointure (max: '.$stock.')']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE panier SET quantite=? WHERE id_user=? AND id_produit=? AND id_pointure=?");
    $stmt->execute([$quantite, $user_id, $id_produit, $id_pointure]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'remove') {
    $id_produit = intval($_POST['id']);
    $id_pointure = intval($_POST['pointure']);
    $stmt = $pdo->prepare("DELETE FROM panier WHERE id_user=? AND id_produit=? AND id_pointure=?");
    $stmt->execute([$user_id, $id_produit, $id_pointure]);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'get') {
    // On récupère les infos produit pour chaque ligne du panier, y compris la valeur de la pointure
    $stmt = $pdo->prepare("
        SELECT pa.id_produit, pa.id_pointure, pa.quantite, 
               p.nom, p.prix, p.id_promotion, pr.discount, 
               (SELECT URL_Image FROM images_produits WHERE id_produit = p.id LIMIT 1) as image,
               po.pointure
        FROM panier pa
        JOIN produit p ON pa.id_produit = p.id
        LEFT JOIN promotion pr ON p.id_promotion = pr.id
        JOIN pointures po ON pa.id_pointure = po.id
        WHERE pa.id_user = ?
    ");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // On utilise TOUJOURS le prix de la base, sans appliquer de promotion
    foreach ($cart as &$item) {
        $item['prix_final'] = $item['prix'];
    }
    echo json_encode(['success' => true, 'cart' => $cart]);
    exit;
}

if ($action === 'check_stock') {
    $stmt = $pdo->prepare("
        SELECT pa.id_produit, pa.id_pointure, pa.quantite, pp.stock, p.nom, po.pointure
        FROM panier pa
        JOIN pointure_produit pp ON pa.id_produit = pp.id_produit AND pa.id_pointure = pp.id_pointure
        JOIN produit p ON pa.id_produit = p.id
        JOIN pointures po ON pa.id_pointure = po.id
        WHERE pa.id_user = ?
    ");
    $stmt->execute([$user_id]);
    $problems = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['quantite'] > $row['stock']) {
            $problems[] = $row['nom'] . ' (pointure ' . $row['pointure'] . ') : stock max ' . $row['stock'];
        }
    }
    if ($problems) {
        echo json_encode(['success' => false, 'message' => 'Stock insuffisant pour : ' . implode(', ', $problems)]);
    } else {
        echo json_encode(['success' => true]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue']); 