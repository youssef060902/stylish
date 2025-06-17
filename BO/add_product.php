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

// Vérification des données requises
$required_fields = ['nom', 'marque', 'categorie', 'type', 'couleur', 'prix', 'quantite', 'statut', 'description'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires']);
        exit();
    }
}

// Vérification des pointures et des stocks si présents
$pointures_stocks = [];
if (isset($_POST['pointures_stocks']) && is_array($_POST['pointures_stocks'])) {
    foreach ($_POST['pointures_stocks'] as $ps_json) {
        $ps_data = json_decode($ps_json, true);
        if (isset($ps_data['pointure_id']) && isset($ps_data['stock']) && is_numeric($ps_data['pointure_id']) && is_numeric($ps_data['stock'])) {
            $pointures_stocks[] = [
                'pointure_id' => intval($ps_data['pointure_id']),
                'stock' => intval($ps_data['stock'])
            ];
        }
    }
}

$total_quantite_from_pointures = array_sum(array_column($pointures_stocks, 'stock'));

if (intval($_POST['quantite']) !== $total_quantite_from_pointures) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'La quantité totale doit correspondre à la somme des stocks des pointures sélectionnées.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Insertion du produit
    $nom = $_POST['nom'];
    $marque = $_POST['marque'];
    $catégorie = $_POST['categorie'];
    $type = $_POST['type'];
    $couleur = $_POST['couleur'];
    $description = $_POST['description'];
    $statut = $_POST['statut'];
    $prix = floatval($_POST['prix']);
    $quantité = intval($_POST['quantite']);
    $id_promotion = !empty($_POST['id_promotion']) ? $_POST['id_promotion'] : null;

    // Si la quantité est 0, forcer le statut à 'rupture de stock'
    if ($quantité === 0) {
        $statut = 'rupture de stock';
    }
    // Sinon, si une promotion est sélectionnée, forcer le statut à 'en promotion'
    else if ($id_promotion) {
        $statut = 'en promotion';
    }
    // Si la quantité n'est pas 0 et qu'il n'y a pas de promotion, forcer le statut à 'en stock'
    else {
        $statut = 'en stock';
    }

    // Si une promotion est sélectionnée, calculer le prix réduit
    if ($id_promotion) {
        $stmt_promo = $pdo->prepare("SELECT discount FROM promotion WHERE id = ?");
        $stmt_promo->execute([$id_promotion]);
        $promotion = $stmt_promo->fetch(PDO::FETCH_ASSOC);
        
        if ($promotion) {
            $discount = floatval($promotion['discount']);
            $prix = $prix * (1 - $discount / 100);
            $prix = round($prix, 2); // Arrondir à 2 décimales
        }
    }

    $stmt = $pdo->prepare("INSERT INTO produit (nom, marque, catégorie, type, couleur, description, statut, prix, quantité, id_promotion, date_ajout) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $stmt->execute([$nom, $marque, $catégorie, $type, $couleur, $description, $statut, $prix, $quantité, $id_promotion]);

    $product_id = $pdo->lastInsertId();

    // Insertion des pointures et stocks associés
    if (!empty($pointures_stocks)) {
        $stmt_insert_pointure_produit = $pdo->prepare("INSERT INTO pointure_produit (id_produit, id_pointure, stock) VALUES (:id_produit, :id_pointure, :stock)");
        foreach ($pointures_stocks as $ps) {
            $stmt_insert_pointure_produit->execute([
                ':id_produit' => $product_id,
                ':id_pointure' => $ps['pointure_id'],
                ':stock' => $ps['stock']
            ]);
        }
    }

    // Gestion des images
    if (isset($_FILES['images'])) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/img/'; // Chemin absolu vers C:/xampp/htdocs/img
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $filename = uniqid() . '_' . $_FILES['images']['name'][$key];
                $filepath = $upload_dir . $filename;

                if (move_uploaded_file($tmp_name, $filepath)) {
                    // Insertion de l'image dans la base de données
                    $stmt = $pdo->prepare("INSERT INTO images_produits (id_produit, URL_Image) VALUES (:id_produit, :url_image)");
                    $stmt->execute([
                        ':id_produit' => $product_id,
                        ':url_image' => 'http://localhost/img/' . $filename
                    ]);
                }
            }
        }
    }

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Produit ajouté avec succès']);

} catch(PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout du produit: ' . $e->getMessage()]);
}
?> 