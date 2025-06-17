<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérification des données requises
$required_fields = ['id', 'nom', 'marque', 'categorie', 'type', 'couleur', 'description', 'statut', 'prix', 'quantite'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
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
    $pdo->beginTransaction();

    // Mise à jour du produit
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
        // Si le produit est en rupture de stock, annuler la promotion et restaurer le prix initial
        if ($id_promotion) {
            // Récupérer le prix actuel et le pourcentage de réduction
            $stmt = $pdo->prepare("
                SELECT p.prix as prix_actuel, pr.discount 
                FROM produit p 
                JOIN promotion pr ON p.id_promotion = pr.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$_POST['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Calculer le prix initial : prix_initial = prix_actuel / (1 - discount/100)
                $prix_initial = $result['prix_actuel'] / (1 - $result['discount']/100);
                
                // Mettre à jour le produit avec le prix initial et sans promotion
                $stmt = $pdo->prepare("UPDATE produit SET prix = ?, id_promotion = NULL WHERE id = ?");
                $stmt->execute([$prix_initial, $_POST['id']]);
                
                // Réinitialiser l'id_promotion pour éviter la mise à jour du prix plus tard
                $id_promotion = null;
            }
        }
    }
    // Sinon, si une promotion est sélectionnée, forcer le statut à 'en promotion'
    else if ($id_promotion) {
        $statut = 'en promotion';
    }
    // Si la quantité n'est pas 0 et qu'il n'y a pas de promotion, forcer le statut à 'en stock'
    else {
        $statut = 'en stock';
    }

    // Récupérer les informations actuelles du produit
    $stmt_current = $pdo->prepare("SELECT prix, id_promotion FROM produit WHERE id = ?");
    $stmt_current->execute([$_POST['id']]);
    $current_product = $stmt_current->fetch(PDO::FETCH_ASSOC);

    // Calculer le prix initial (sans promotion)
    $prix_initial = $prix;
    if ($current_product['id_promotion']) {
        $stmt_promo = $pdo->prepare("SELECT discount FROM promotion WHERE id = ?");
        $stmt_promo->execute([$current_product['id_promotion']]);
        $promotion = $stmt_promo->fetch(PDO::FETCH_ASSOC);
        if ($promotion) {
            $discount = floatval($promotion['discount']);
            $prix_initial = $prix / (1 - $discount / 100);
            $prix_initial = round($prix_initial, 2);
        }
    }

    // Si le produit a déjà une promotion
    if ($current_product['id_promotion']) {
        // Si on essaie d'appliquer une nouvelle promotion
        if ($id_promotion && $id_promotion != $current_product['id_promotion']) {
            // Calculer le nouveau prix avec la nouvelle promotion
            $stmt_promo = $pdo->prepare("SELECT discount FROM promotion WHERE id = ?");
            $stmt_promo->execute([$id_promotion]);
            $promotion = $stmt_promo->fetch(PDO::FETCH_ASSOC);
            if ($promotion) {
                $discount = floatval($promotion['discount']);
                $prix = $prix_initial * (1 - $discount / 100);
                $prix = round($prix, 2);
            }
        }
        // Si on retire la promotion, restaurer le prix initial
        if (!$id_promotion) {
            $prix = $prix_initial;
        }
    } else {
        // Si le produit n'a pas de promotion, on peut lui en appliquer une
        if ($id_promotion) {
            $stmt_promo = $pdo->prepare("SELECT discount FROM promotion WHERE id = ?");
            $stmt_promo->execute([$id_promotion]);
            $promotion = $stmt_promo->fetch(PDO::FETCH_ASSOC);
            
            if ($promotion) {
                $discount = floatval($promotion['discount']);
                $prix = $prix_initial * (1 - $discount / 100);
                $prix = round($prix, 2);
            }
        }
    }

    $stmt = $pdo->prepare("UPDATE produit SET 
        nom = ?,
        marque = ?,
        catégorie = ?,
        type = ?,
        couleur = ?,
        description = ?,
        statut = ?,
        prix = ?,
        quantité = ?,
        id_promotion = ?,
        date_modification = NOW()
        WHERE id = ?");

    $stmt->execute([
        $nom,
        $marque,
        $catégorie,
        $type,
        $couleur,
        $description,
        $statut,
        $prix,
        $quantité,
        $id_promotion,
        $_POST['id']
    ]);

    // Suppression des anciennes pointures du produit
    $stmt_delete_pointures = $pdo->prepare("DELETE FROM pointure_produit WHERE id_produit = ?");
    $stmt_delete_pointures->execute([$_POST['id']]);

    // Insertion des nouvelles pointures et stocks associés
    if (!empty($pointures_stocks)) {
        $stmt_insert_pointure_produit = $pdo->prepare("INSERT INTO pointure_produit (id_produit, id_pointure, stock) VALUES (?, ?, ?)");
        foreach ($pointures_stocks as $ps) {
            $stmt_insert_pointure_produit->execute([
                $_POST['id'],
                $ps['pointure_id'],
                $ps['stock']
            ]);
        }
    }

    // Gestion des nouvelles images si présentes
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/img/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $filename = uniqid() . '_' . $_FILES['images']['name'][$key];
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($tmp_name, $filepath)) {
                    $stmt = $pdo->prepare("INSERT INTO images_produits (id_produit, URL_Image) VALUES (?, ?)");
                    $stmt->execute([
                        $_POST['id'],
                        'http://localhost/img/' . $filename
                    ]);
                }
            }
        }
    }

    // Suppression des images sélectionnées si présentes
    if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
        $delete_images = json_decode($_POST['delete_images'], true);
        foreach ($delete_images as $image_id) {
            // Récupération du chemin de l'image
            $stmt = $pdo->prepare("SELECT URL_Image FROM images_produits WHERE id = ?");
            $stmt->execute([$image_id]);
            $image_path = $stmt->fetchColumn();

            // Suppression du fichier physique
            if ($image_path && file_exists(str_replace('http://localhost/', $_SERVER['DOCUMENT_ROOT'] . '/', $image_path))) {
                unlink(str_replace('http://localhost/', $_SERVER['DOCUMENT_ROOT'] . '/', $image_path));
            }

            // Suppression de l'enregistrement dans la base de données
            $stmt = $pdo->prepare("DELETE FROM images_produits WHERE id = ?");
            $stmt->execute([$image_id]);
        }
    }

    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Produit mis à jour avec succès']);

} catch(PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du produit: ' . $e->getMessage()]);
}
?> 