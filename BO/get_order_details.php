<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo "Accès interdit.";
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "ID de commande manquant.";
    exit();
}

$order_id = $_GET['id'];
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Récupérer les informations de base de la commande et de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT
            c.*,
            u.prenom, u.nom, u.email, u.phone, u.image AS user_image,
            CONCAT(u.prenom, ' ', u.nom) as user_name
        FROM commande c
        JOIN user u ON c.id_user = u.id
        WHERE c.id = :id
    ");
    $stmt->execute(['id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo "Commande non trouvée.";
        exit();
    }

    // 2. Récupérer les produits de la commande
    $stmt_products = $pdo->prepare("
        SELECT
            p.nom,
            p.marque,
            p.couleur,
            po.pointure,
            cp.quantite,
            cp.prix_unitaire,
            (SELECT URL_Image FROM images_produits WHERE id_produit = p.id ORDER BY id ASC LIMIT 1) as product_image
        FROM commande_produit cp
        JOIN produit p ON cp.id_produit = p.id
        JOIN pointures po ON cp.id_pointure = po.id
        WHERE cp.id_commande = :id
    ");
    $stmt_products->execute(['id' => $order_id]);
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    die("Erreur de base de données : " . $e->getMessage());
}

// --- Calculs des totaux (avec gestion des réductions) ---
$subtotal = 0;
foreach ($products as $product) {
    $subtotal += $product['prix_unitaire'] * $product['quantite'];
}
$shipping_cost = 7.00; // Coût de livraison fixe
$total_in_db = $order['total'];
$discount = ($subtotal + $shipping_cost) - $total_in_db;

// Rendu HTML
?>
<div class="container-fluid">
    <div class="row">
        <!-- Informations Client et Commande -->
        <div class="col-lg-5 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-user-circle me-2"></i>Informations Client</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if (!empty($order['user_image'])): ?>
                            <img src="<?php echo htmlspecialchars($order['user_image']); ?>" alt="Client" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 15px;">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; border-radius: 50%; background-color: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #495057; margin-right: 15px;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <strong><?php echo htmlspecialchars($order['user_name']); ?></strong><br>
                            <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>"><?php echo htmlspecialchars($order['email']); ?></a>
                        </div>
                    </div>
                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                    <hr>
                    <p class="mb-0"><strong>Adresse de livraison:</strong><br><?php echo nl2br(htmlspecialchars($order['adresse_livraison'])); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-7 mb-4">
            <div class="card h-100 shadow-sm">
                 <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Détails de la Commande</h6>
                    <span class="badge bg-primary"><?php echo ucfirst($order['statut']); ?></span>
                </div>
                <div class="card-body">
                    <p><strong>Numéro:</strong> #<?php echo $order['id']; ?></p>
                    <p class="mb-4"><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?></p>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <span>Sous-total articles :</span>
                        <span><?php echo number_format($subtotal, 2, ',', ' '); ?> DT</span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Coût de livraison :</span>
                        <span><?php echo number_format($shipping_cost, 2, ',', ' '); ?> DT</span>
                    </div>

                    <?php if ($discount > 0.01): ?>
                    <div class="d-flex justify-content-between text-danger">
                        <span>Réduction (Coupon) :</span>
                        <span>- <?php echo number_format($discount, 2, ',', ' '); ?> DT</span>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between fs-5 fw-bold text-success">
                        <span>Total Payé :</span>
                        <span><?php echo number_format($total_in_db, 2, ',', ' '); ?> DT</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits Commandés -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-box-open me-2"></i>Articles Commandés</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Produit</th>
                            <th>Description</th>
                            <th class="text-center">Pointure</th>
                            <th class="text-center">Quantité</th>
                            <th class="text-end">Prix Unitaire</th>
                            <th class="text-end">Sous-total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($product['product_image'] ?: 'https://via.placeholder.com/150'); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['nom']); ?>" style="width: 60px; height: 60px; object-fit: cover;">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['nom']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($product['marque'] . ' / ' . $product['couleur']); ?></small>
                            </td>
                            <td class="text-center"><?php echo htmlspecialchars($product['pointure']); ?></td>
                            <td class="text-center"><?php echo $product['quantite']; ?></td>
                            <td class="text-end"><?php echo number_format($product['prix_unitaire'], 2, ',', ' '); ?> DT</td>
                            <td class="text-end fw-bold"><?php echo number_format($product['prix_unitaire'] * $product['quantite'], 2, ',', ' '); ?> DT</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 