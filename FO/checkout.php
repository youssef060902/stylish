<?php
// Inclure le header et les styles
 include 'header.php'; 
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

// Traitement du formulaire de commande
$message = '';
$errors = [];
$coupon_applied = false;
$coupon_discount = 0;
$coupon_code = '';
$coupon_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adresse_livraison'])) {
    $coupon_code = trim($_POST['coupon'] ?? '');
    $stmt = $pdo->prepare("
        SELECT pa.id_produit, pa.id_pointure, pa.quantite, pp.stock, p.nom, po.pointure, p.prix
        FROM panier pa
        JOIN pointure_produit pp ON pa.id_produit = pp.id_produit AND pa.id_pointure = pp.id_pointure
        JOIN produit p ON pa.id_produit = p.id
        JOIN pointures po ON pa.id_pointure = po.id
        WHERE pa.id_user = ?
    ");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $problems = [];
    foreach ($cart as $row) {
        if ($row['quantite'] > $row['stock']) {
            $problems[] = $row['nom'] . ' (pointure ' . $row['pointure'] . ') : stock max ' . $row['stock'];
        }
    }
    if ($problems) {
        $errors[] = "Stock insuffisant pour :<br>" . implode('<br>', $problems);
    } else {
        $total_produits = 0;
        foreach ($cart as $row) {
            $total_produits += $row['prix'] * $row['quantite'];
        }
        // Gestion du coupon
        if ($coupon_code !== '') {
            $stmt = $pdo->prepare("SELECT discount, statut FROM coupon WHERE code = ?");
            $stmt->execute([$coupon_code]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($coupon && $coupon['discount'] > 0 && $coupon['statut'] === 'active') {
                $coupon_applied = true;
                $coupon_discount = (float)$coupon['discount'];
                $coupon_message = "Coupon appliqué : -" . (int)$coupon_discount . "%";
            } elseif ($coupon && $coupon['statut'] !== 'active') {
                $errors[] = "Ce coupon est inactif. Veuillez en saisir un autre ou laisser vide.";
            } else {
                $errors[] = "Code coupon invalide. Veuillez corriger ou laisser vide.";
            }
        }
        $reduction = $coupon_applied ? round($total_produits * $coupon_discount / 100, 2) : 0;
        $livraison = 7.00;
        $total = $total_produits - $reduction + $livraison;
        $adresse = trim($_POST['adresse_livraison']);
        if (empty($adresse)) {
            $errors[] = "Veuillez saisir une adresse de livraison.";
        }
        // N'enregistrer la commande que s'il n'y a pas d'erreur
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO commande (id_user, total, adresse_livraison) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $total, $adresse]);
            $id_commande = $pdo->lastInsertId();
            foreach ($cart as $row) {
                $stmt = $pdo->prepare("INSERT INTO commande_produit (id_commande, id_produit, id_pointure, prix_unitaire, quantite) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id_commande, $row['id_produit'], $row['id_pointure'], $row['prix'], $row['quantite']]);
                $stmt = $pdo->prepare("UPDATE pointure_produit SET stock = stock - ? WHERE id_produit = ? AND id_pointure = ?");
                $stmt->execute([$row['quantite'], $row['id_produit'], $row['id_pointure']]);
                // Décrémenter aussi la quantité globale du produit
                $stmt = $pdo->prepare("UPDATE produit SET quantité = quantité - ? WHERE id = ?");
                $stmt->execute([$row['quantite'], $row['id_produit']]);
                // Si la quantité devient 0 ou moins, mettre le statut à 'rupture de stock'
                $stmt = $pdo->prepare("SELECT quantité FROM produit WHERE id = ?");
                $stmt->execute([$row['id_produit']]);
                $prod = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($prod && $prod['quantité'] <= 0) {
                    $stmt = $pdo->prepare("UPDATE produit SET statut = 'rupture de stock' WHERE id = ?");
                    $stmt->execute([$row['id_produit']]);
                }
            }
            $stmt = $pdo->prepare("DELETE FROM panier WHERE id_user = ?");
            $stmt->execute([$user_id]);
            $message = "<div class='alert alert-success fw-bold'>Commande validée avec succès !</div>";
        }
    }
}

// Récupérer le panier pour affichage
$stmt = $pdo->prepare("
    SELECT pa.id_produit, pa.id_pointure, pa.quantite, pp.stock, p.nom, po.pointure, p.prix
    FROM panier pa
    JOIN pointure_produit pp ON pa.id_produit = pp.id_produit AND pa.id_pointure = pp.id_pointure
    JOIN produit p ON pa.id_produit = p.id
    JOIN pointures po ON pa.id_pointure = po.id
    WHERE pa.id_user = ?
");
$stmt->execute([$user_id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculs pour affichage
$total_produits = 0;
foreach ($cart as $item) {
    $total_produits += $item['prix'] * $item['quantite'];
}
$reduction = $coupon_applied ? round($total_produits * $coupon_discount / 100, 2) : 0;
$livraison = 7.00;
$total = $total_produits - $reduction + $livraison;

// Récupérer la première image de chaque produit
function getProductImage($pdo, $id_produit) {
    $imgStmt = $pdo->prepare("SELECT URL_Image FROM images_produits WHERE id_produit = ? LIMIT 1");
    $imgStmt->execute([$id_produit]);
    $img = $imgStmt->fetch(PDO::FETCH_ASSOC);
    return $img && !empty($img['URL_Image']) ? $img['URL_Image'] : 'images/default_product.jpg';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/all.min.css" rel="stylesheet">
  <link href="css/vendor.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <title>Passer la commande</title>
  <style>
    .checkout-title {
      font-size: 2rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 2rem;
      text-align: center;
    }
    .checkout-empty {
      text-align: center;
      color: #888;
      font-size: 1.2rem;
      margin: 3rem 0;
    }
    .checkout-table {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 8px #e0e0e044;
      margin-bottom: 2rem;
      width: 100%;
      max-width: 900px;
      margin-left: auto;
      margin-right: auto;
    }
    .checkout-table th, .checkout-table td {
      text-align: left;
      padding: 12px 10px;
      border-bottom: 1px solid #eee;
    }
    .checkout-table th {
      background: #f8f9fa;
      font-weight: 600;
    }
    .checkout-table tfoot td {
      font-weight: bold;
      color: #e74c3c;
      font-size: 1.1em;
      border-top: 2px solid #e74c3c33;
    }
    .form-section {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 8px #e0e0e044;
      padding: 2rem;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    .recap-table {
      max-width: 400px;
      margin: 0 auto 2rem auto;
      font-size: 1.1em;
    }
    .recap-table td {
      padding: 6px 10px;
    }
    .recap-table .label {
      color: #555;
    }
    .recap-table .total {
      font-weight: bold;
      color: #e74c3c;
      font-size: 1.2em;
    }
  </style>
</head>
<body>
  <section class="container py-5">
    <h2 class="checkout-title">Récapitulatif de la commande</h2>
    <?php if ($message) echo $message; ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $err) echo $err . '<br>'; ?>
      </div>
    <?php endif; ?>
    <?php if (empty($cart)): ?>
      <div class="checkout-empty">Votre panier est vide.</div>
    <?php else: ?>
      <table class="checkout-table table table-bordered">
        <thead>
          <tr>
            <th>Produit</th>
            <th>Pointure</th>
            <th>Quantité</th>
            <th>Prix unitaire</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cart as $item): ?>
            <tr>
              <td><?php echo htmlspecialchars($item['nom']); ?></td>
              <td><?php echo htmlspecialchars($item['pointure']); ?></td>
              <td><?php echo htmlspecialchars($item['quantite']); ?></td>
              <td><?php echo number_format($item['prix'], 2, ',', ' '); ?> DT</td>
              <td><?php echo number_format($item['prix'] * $item['quantite'], 2, ',', ' '); ?> DT</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <table class="recap-table">
        <tr><td class="label">Total produits :</td><td><?php echo number_format($total_produits, 2, ',', ' '); ?> DT</td></tr>
        <?php if ($coupon_applied): ?>
        <tr><td class="label">Réduction coupon :</td><td>-<?php echo number_format($reduction, 2, ',', ' '); ?> DT</td></tr>
        <?php endif; ?>
        <tr><td class="label">Livraison :</td><td>+<?php echo number_format($livraison, 2, ',', ' '); ?> DT</td></tr>
        <tr><td class="total">Total à payer :</td><td class="total"><?php echo number_format($total, 2, ',', ' '); ?> DT</td></tr>
      </table>
      <?php if ($coupon_message): ?>
        <div class="mb-2 text-<?php echo $coupon_applied ? 'success' : 'danger'; ?> text-center"><?php echo $coupon_message; ?></div>
      <?php endif; ?>
      <form method="post" class="form-section mt-4">
        <div class="mb-3 row">
          <label for="adresse_livraison" class="col-sm-4 col-form-label">Adresse de livraison</label>
          <div class="col-sm-8">
            <input type="text" class="form-control" id="adresse_livraison" name="adresse_livraison" required placeholder="Saisissez votre adresse de livraison" value="<?php echo isset($_POST['adresse_livraison']) ? htmlspecialchars($_POST['adresse_livraison']) : '' ?>">
          </div>
        </div>
        <div class="mb-3 row">
          <label for="coupon" class="col-sm-4 col-form-label">Code promo</label>
          <div class="col-sm-8">
            <input type="text" class="form-control" id="coupon" name="coupon" placeholder="Entrez votre code promo" value="<?php echo htmlspecialchars($coupon_code); ?>">
          </div>
        </div>
        <div class="mb-3 row">
          <div class="col-sm-4"></div>
          <div class="col-sm-8">
            <button type="submit" class="btn btn-danger btn-lg w-100">Commander</button>
          </div>
        </div>
      </form>
    <?php endif; ?>
  </section>
  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/plugins.js"></script>
  <script src="js/script.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script></body>
</html> 