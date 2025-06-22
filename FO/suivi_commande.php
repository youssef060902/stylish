<?php
// Désactiver le rapport d'erreurs pour les utilisateurs finaux
error_reporting(0);
ini_set('display_errors', 0);

// --- Connexion à la base de données ---
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';
$pdo = null; // Initialiser à null

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Gérer l'erreur de connexion de manière "propre"
    $page_title = "Erreur";
    $error_message = "Impossible de se connecter à la base de données. Veuillez réessayer plus tard.";
    // Inclure un template d'erreur simple ici si nécessaire
    exit($error_message);
}

// --- Récupération des informations de la commande ---
$order = null;
$order_id = isset($_GET['id_commande']) ? filter_var($_GET['id_commande'], FILTER_VALIDATE_INT) : false;

if ($order_id) {
    $stmt = $pdo->prepare("
        SELECT 
            c.id, c.date_commande, c.statut, c.adresse_livraison, c.date_livraison, c.total,
            u.prenom, u.nom
        FROM commande c
        JOIN user u ON c.id_user = u.id
        WHERE c.id = :id
    ");
    $stmt->execute([':id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $stmt_products = $pdo->prepare("
            SELECT p.nom, ip.URL_Image, cp.quantite, cp.prix_unitaire, po.pointure
            FROM commande_produit cp
            JOIN produit p ON cp.id_produit = p.id
            JOIN pointures po ON cp.id_pointure = po.id
            LEFT JOIN (
                SELECT id_produit, MIN(URL_Image) as URL_Image 
                FROM images_produits 
                GROUP BY id_produit
            ) ip ON p.id = ip.id_produit
            WHERE cp.id_commande = :id
        ");
        $stmt_products->execute([':id' => $order_id]);
        $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

        // Calculs pour le détail du prix
        $subtotal = 0;
        foreach ($products as $product) {
            $subtotal += $product['prix_unitaire'] * $product['quantite'];
        }
        $shipping_cost = 7.00; // Coût de livraison fixe
        $total_in_db = $order['total'];
        $discount = ($subtotal + $shipping_cost) - $total_in_db;
    }
}
$page_title = $order ? "Suivi de votre commande #" . htmlspecialchars($order['id']) : "Commande introuvable";

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Stylish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .tracking-container {
            max-width: 900px;
            margin: 50px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }
        .tracking-header h1 {
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }
        .tracking-header .lead {
            color: #6c757d;
        }
        .timeline {
            position: relative;
            padding: 2rem 0;
            list-style: none;
        }
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 18px;
            height: 100%;
            width: 4px;
            background: #e9ecef;
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 2.5rem;
        }
        .timeline-item .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #adb5bd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            border: 4px solid #f8f9fa;
            z-index: 1;
        }
        .timeline-item.active .timeline-icon {
            background-color: #0d6efd;
            color: #fff;
        }
        .timeline-item .timeline-content {
            margin-left: 60px;
            padding-top: 5px;
        }
        .timeline-item .timeline-content h5 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .timeline-item.active .timeline-content h5 {
            color: #0d6efd;
        }
        .timeline-item .timeline-content p {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .order-summary {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .product-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container">
    <?php if ($order): ?>
        <div class="tracking-container">
            <div class="tracking-header text-center">
                <h1>Suivi de Commande #<?php echo htmlspecialchars($order['id']); ?></h1>
                <p class="lead">Merci pour votre commande, <?php echo htmlspecialchars($order['prenom']); ?> !</p>
                <?php if ($order['statut'] === 'livré'): ?>
                    <div class="alert alert-success mt-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i>Votre commande a été livrée.
                    </div>
                <?php elseif ($order['date_livraison']): ?>
                     <div class="alert alert-info mt-3" role="alert">
                        <i class="fas fa-shipping-fast me-2"></i>Livraison estimée le : <strong><?php echo date('d/m/Y', strtotime($order['date_livraison'])); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
            
            <hr class="my-4">

            <?php
                $statuses = ['confirmé', 'en préparation', 'expédié', 'livré'];
                $current_status_index = array_search($order['statut'], $statuses);
                if ($current_status_index === false && $order['statut'] === 'en attente') {
                    $current_status_index = -1; // Avant même confirmé
                } elseif ($current_status_index === false) {
                    $current_status_index = 0; // fallback
                }

                $status_details = [
                    'confirmé' => ['icon' => 'fa-check', 'label' => 'Commande Confirmée', 'date' => $order['date_commande']],
                    'en préparation' => ['icon' => 'fa-box-open', 'label' => 'En Préparation', 'date' => null],
                    'expédié' => ['icon' => 'fa-truck', 'label' => 'Commande Expédiée', 'date' => $order['statut'] === 'expédié' || $order['statut'] === 'livré' ? date('Y-m-d') : null],
                    'livré' => ['icon' => 'fa-home', 'label' => 'Commande Livrée', 'date' => $order['statut'] === 'livré' ? $order['date_livraison'] : null]
                ];
            ?>

            <ul class="timeline">
                <?php foreach ($status_details as $key => $details): ?>
                    <?php 
                        $is_active = array_search($key, $statuses) <= $current_status_index;
                        // Cas spécial pour 'en préparation' qui n'a pas de date fixe mais on peut afficher la date de livraison estimée
                        $date_display = '';
                        if ($is_active && $details['date']) {
                            $date_display = date('d/m/Y', strtotime($details['date']));
                        } elseif ($key === 'en préparation' && $is_active && $order['date_livraison']) {
                            $date_display = 'Livraison estimée le ' . date('d/m/Y', strtotime($order['date_livraison']));
                        }
                    ?>
                    <li class="timeline-item <?php echo $is_active ? 'active' : ''; ?>">
                        <div class="timeline-icon">
                            <i class="fas <?php echo $details['icon']; ?>"></i>
                        </div>
                        <div class="timeline-content">
                            <h5><?php echo $details['label']; ?></h5>
                            <?php if (!empty($date_display)): ?>
                                <p><?php echo $date_display; ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <hr class="my-4">

            <div class="row">
                <div class="col-md-7">
                    <h4>Résumé de la commande</h4>
                    <div class="order-summary mt-3">
                        <?php foreach($products as $product): ?>
                        <div class="product-item d-flex align-items-center mb-3">
                            <img src="<?php echo htmlspecialchars($product['URL_Image'] ?: 'images/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="me-3">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($product['nom']); ?></h6>
                                <small class="text-muted">Pointure: <?php echo htmlspecialchars($product['pointure']); ?></small><br>
                                <small class="text-muted">Qté: <?php echo $product['quantite']; ?></small>
                            </div>
                            <div class="fw-bold fs-6">
                                <?php echo number_format($product['prix_unitaire'] * $product['quantite'], 2, ',', ' '); ?> DT
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <hr class="my-3">

                        <div class="price-breakdown">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Sous-total</span>
                                <span class="text-muted"><?php echo number_format($subtotal, 2, ',', ' '); ?> DT</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Frais de livraison</span>
                                <span class="text-muted"><?php echo number_format($shipping_cost, 2, ',', ' '); ?> DT</span>
                            </div>
                            <?php if ($discount > 0.01): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-success fw-bold">Réduction appliquée</span>
                                    <span class="text-success fw-bold">- <?php echo number_format($discount, 2, ',', ' '); ?> DT</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-3">
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold">Total Payé</h5>
                            <h5 class="mb-0 fw-bold"><?php echo number_format($order['total'], 2, ',', ' '); ?> DT</h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <h4>Adresse de livraison</h4>
                    <div class="order-summary mt-3" style="padding: 20px;">
                        <p class="mb-1"><strong><?php echo htmlspecialchars($order['prenom'] . ' ' . $order['nom']); ?></strong></p>
                        <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($order['adresse_livraison'])); ?></p>
                    </div>
                </div>
            </div>
            
        </div>
    <?php else: ?>
        <div class="tracking-container text-center">
             <div class="alert alert-warning">
                <h1><i class="fas fa-exclamation-triangle"></i> Commande Introuvable</h1>
                <p class="mt-3">Désolé, nous n'avons pas pu trouver de commande correspondant à cet identifiant.</p>
                <p>Veuillez vérifier le lien ou retourner à l'accueil.</p>
                <a href="index.php" class="btn btn-primary mt-2">Retour à l'accueil</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
