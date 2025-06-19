<?php
// Inclure le header et les styles
include 'header.php';

// Connexion à la base de données
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adresse_livraison'])) {
    // 1. Récupérer le panier de l'utilisateur
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

    // 2. Vérifier le stock pour chaque ligne
    $problems = [];
    foreach ($cart as $row) {
        if ($row['quantite'] > $row['stock']) {
            $problems[] = $row['nom'] . ' (pointure ' . $row['pointure'] . ') : stock max ' . $row['stock'];
        }
    }
    if ($problems) {
        $errors[] = "Stock insuffisant pour :<br>" . implode('<br>', $problems);
    } else {
        // 3. Créer la commande
        $total = 0;
        foreach ($cart as $row) {
            $total += $row['prix'] * $row['quantite'];
        }
        $adresse = trim($_POST['adresse_livraison']);
        if (empty($adresse)) {
            $errors[] = "Veuillez saisir une adresse de livraison.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO commande (id_user, total, adresse_livraison) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $total, $adresse]);
            $id_commande = $pdo->lastInsertId();

            // 4. Insérer les lignes dans commande_produit et mettre à jour le stock
            foreach ($cart as $row) {
                $stmt = $pdo->prepare("INSERT INTO commande_produit (id_commande, id_produit, id_pointure, prix_unitaire, quantite) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id_commande, $row['id_produit'], $row['id_pointure'], $row['prix'], $row['quantite']]);
                // Mise à jour du stock
                $stmt = $pdo->prepare("UPDATE pointure_produit SET stock = stock - ? WHERE id_produit = ? AND id_pointure = ?");
                $stmt->execute([$row['quantite'], $row['id_produit'], $row['id_pointure']]);
                // Décrémenter aussi la quantité globale du produit
                $stmt = $pdo->prepare("UPDATE produit SET quantité = quantité - ? WHERE id = ?");
                $stmt->execute([$row['quantite'], $row['id_produit']]);
            }

            // 5. Vider le panier
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
          <?php $total = 0; ?>
          <?php foreach ($cart as $item): ?>
            <?php $total += $item['prix'] * $item['quantite']; ?>
            <tr>
              <td><?php echo htmlspecialchars($item['nom']); ?></td>
              <td><?php echo htmlspecialchars($item['pointure']); ?></td>
              <td><?php echo htmlspecialchars($item['quantite']); ?></td>
              <td><?php echo number_format($item['prix'], 2, ',', ' '); ?> DT</td>
              <td><?php echo number_format($item['prix'] * $item['quantite'], 2, ',', ' '); ?> DT</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="4" class="text-end">Total :</td>
            <td><?php echo number_format($total, 2, ',', ' '); ?> DT</td>
          </tr>
        </tfoot>
      </table>
      <form method="post" class="form-section mt-4">
        <div class="mb-3 row">
          <label for="adresse_livraison" class="col-sm-4 col-form-label">Adresse de livraison</label>
          <div class="col-sm-8">
            <input type="text" class="form-control" id="adresse_livraison" name="adresse_livraison" required placeholder="Saisissez votre adresse de livraison" value="<?php echo isset($_POST['adresse_livraison']) ? htmlspecialchars($_POST['adresse_livraison']) : '' ?>">
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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 