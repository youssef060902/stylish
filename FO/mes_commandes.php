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
// Récupérer les commandes de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM commande WHERE id_user = ? ORDER BY date_commande DESC");
$stmt->execute([$user_id]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Pour chaque commande, récupérer les produits
function getProduitsCommande($pdo, $id_commande) {
    $stmt = $pdo->prepare("SELECT cp.*, p.nom FROM commande_produit cp JOIN produit p ON cp.id_produit = p.id WHERE cp.id_commande = ?");
    $stmt->execute([$id_commande]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes commandes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/all.min.css" rel="stylesheet">
  <link href="css/vendor.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
    <style>
        .commande-card { box-shadow: 0 2px 8px #e0e0e044; border-radius: 10px; background: #fff; margin-bottom: 2rem; }
        .commande-header { background: #f8f9fa; border-radius: 10px 10px 0 0; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .commande-status { font-weight: bold; }
        .commande-status.en-attente { color: #e67e22; }       /* Orange */
        .commande-status.confirmé { color: #f39c12; }         /* Jaune-orangé */
        .commande-status.en-préparation { color: #3498db; }  /* Bleu */
        .commande-status.expédié { color: #8e44ad; }           /* Violet */
        .commande-status.livré { color: #27ae60; }            /* Vert */
        .commande-body { padding: 1.5rem; }
        .table th, .table td { vertical-align: middle; }
        .badge-coupon { background: #e74c3c; color: #fff; font-size: 0.95em; }
    </style>
</head>
<body style="background:#f6f6f6;">
<div class="container py-5">
    <h2 class="mb-4 text-center">Mes commandes</h2>
    <?php if (empty($commandes)): ?>
        <div class="alert alert-info text-center">Vous n'avez passé aucune commande pour le moment.</div>
    <?php else: ?>
        <?php foreach ($commandes as $commande): ?>
            <div class="commande-card mb-4">
                <div class="commande-header">
                    <div>
                        <span class="me-3"><b>Date :</b> <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></span>
                        <span class="commande-status <?php echo str_replace(' ', '-', $commande['statut']); ?>">
                            <?php echo ucfirst($commande['statut']); ?>
                        </span>
                    </div>
                    <div>
                        <span class="me-3"><b>Total :</b> <?php echo number_format($commande['total'], 2, ',', ' '); ?> DT</span>
                    </div>
                </div>
                <div class="commande-body">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produit</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $produits = getProduitsCommande($pdo, $commande['id']);
                        foreach ($produits as $prod): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prod['nom']); ?></td>
                                <td><?php echo number_format($prod['prix_unitaire'], 2, ',', ' '); ?> DT</td>
                                <td><?php echo (int)$prod['quantite']; ?></td>
                                <td><?php echo number_format($prod['prix_unitaire'] * $prod['quantite'], 2, ',', ' '); ?> DT</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="mt-3">
                        <span class="me-3"><b>Frais de livraison :</b> 7.00 DT</span>
                        <?php
                        // Vérifier si un coupon a été appliqué (en regardant la différence entre total et somme produits+livraison)
                        $sous_total = 0;
                        foreach ($produits as $prod) {
                            $sous_total += $prod['prix_unitaire'] * $prod['quantite'];
                        }
                        $coupon = round($sous_total + 7.00 - $commande['total'], 2);
                        if ($coupon > 0.01) {
                            echo '<span class="badge badge-coupon ms-2">Coupon appliqué : -' . number_format($coupon, 2, ',', ' ') . ' DT</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script src="js/jquery-1.11.0.min.js"></script>
<script src="js/plugins.js"></script>
<script src="js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 