<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
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
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération des produits
try {
    $stmt = $pdo->query("SELECT p.*, pr.nom as promotion_nom, pr.discount 
                         FROM produit p 
                         LEFT JOIN promotion pr ON p.id_promotion = pr.id 
                         ORDER BY p.id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de récupération des produits : " . $e->getMessage());
}

// Récupération des pointures
try {
    $stmt_pointures = $pdo->query("SELECT * FROM pointures ORDER BY pointure ASC");
    $pointures = $stmt_pointures->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de récupération des pointures : " . $e->getMessage());
}

// Récupération des promotions actives
try {
    $stmt_promotions = $pdo->query("SELECT id, nom, discount, date_debut, date_fin 
                                   FROM promotion 
                                   WHERE date_debut <= NOW() AND date_fin >= NOW() 
                                   ORDER BY date_debut DESC");
    $promotions = $stmt_promotions->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de récupération des promotions : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Produits - Stylish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #2c3e50;
            color: white;
            padding-top: 20px;
            position: fixed;
            width: inherit;
            max-width: inherit;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 10px 20px;
            margin: 5px 0;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background: #3498db;
            color: white;
        }
        .main-content {
            padding: 20px;
            margin-left: 16.666667%; /* Pour compenser la largeur de la sidebar */
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .status-stock { background-color: #28a745; color: white; }
        .status-promo { background-color: #ffc107; color: black; }
        .status-rupture { background-color: #dc3545; color: white; }
        .custom-file-input {
            display: none;
        }
        .camera-upload-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .camera-icon-label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #f3f3f3;
            border: 2px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s, border 0.2s;
            position: relative;
            font-size: 0;
        }
        .camera-icon-label:hover {
            background: #e9ecef;
            border: 2px solid #dc3545;
            box-shadow: 0 4px 16px rgba(220,53,69,0.10);
        }
        .camera-icon-label svg {
            width: 36px;
            height: 36px;
            color: #dc3545;
            display: block;
        }
        .camera-upload-text {
            font-size: 0.95rem;
            color: #888;
            margin-top: 4px;
            text-align: center;
        }
        .image-preview-wrapper {
            position: relative;
            display: inline-block;
            margin: 5px;
        }
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #dc3545;
            box-shadow: 0 2px 8px rgba(220,53,69,0.10);
        }
        .image-remove-x {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #dc3545;
            color: #fff;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            z-index: 2;
        }
        .images-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        /* Nouveaux styles pour la modal de détails */
        .product-details-modal .modal-content {
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .product-details-modal .modal-header {
            background: #f8f9fa;
            border-bottom: none;
            padding: 1.5rem;
        }

        .product-details-modal .modal-body {
            padding: 0;
        }

        .product-details-modal .carousel {
            border-radius: 0;
            overflow: hidden;
        }

        .product-details-modal .carousel-item img {
            height: 400px;
            object-fit: cover;
        }

        .product-details-modal .product-info {
            padding: 2rem;
            background: #fff;
        }

        .product-details-modal .product-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .product-details-modal .product-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .product-details-modal .meta-item {
            background: #f8f9fa;
            padding: 0.8rem;
            border-radius: 8px;
        }

        .product-details-modal .meta-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 0.3rem;
        }

        .product-details-modal .meta-value {
            font-weight: 500;
            color: #2c3e50;
        }

        .product-details-modal .product-description {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }

        .product-details-modal .sizes-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .product-details-modal .size-badge {
            background: #e9ecef;
            color: #2c3e50;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-details-modal .size-badge .stock {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .product-details-modal .price-tag {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 1rem 0;
        }

        .product-details-modal .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Stylish Admin</h3>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-users me-2"></i> Utilisateurs</a>
                    <a class="nav-link active" href="products.php"><i class="fas fa-product-hunt me-2"></i> Produits</a>
                    <a class="nav-link" href="promotion.php"><i class="fas fa-box me-2"></i> Promotions</a>
                    <a class="nav-link" href="#"><i class="fas fa-cog me-2"></i> Paramètres</a>
                    <div class="mt-auto pt-3 border-top border-secondary">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
                    </div>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0">Gestion des Produits</h2>
                        <div class="ms-3 badge bg-primary" style="font-size: 1.1em;">
                            <i class="fas fa-box me-1"></i>
                            <span id="product-count"><?php echo count($products); ?></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="input-group" style="width: 300px;">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="searchProduct" class="form-control" placeholder="Rechercher un produit...">
                        </div>
                        <button class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus me-2"></i>Nouveau produit
                        </button>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filtres</h5>
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">Catégorie</label>
                                <select class="form-select filter-select" name="categorie">
                                    <option value="">Toutes</option>
                                    <option value="homme">Homme</option>
                                    <option value="femme">Femme</option>
                                    <option value="enfant">Enfant</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Type</label>
                                <select class="form-select filter-select" name="type">
                                    <option value="">Tous</option>
                                    <option value="running">Running</option>
                                    <option value="casual">Casual</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Marque</label>
                                <select class="form-select filter-select" name="marque">
                                    <option value="">Toutes</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT DISTINCT marque FROM produit ORDER BY marque");
                                    while ($row = $stmt->fetch()) {
                                        echo '<option value="' . htmlspecialchars($row['marque']) . '">' . htmlspecialchars($row['marque']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Couleur</label>
                                <select class="form-select filter-select" name="couleur">
                                    <option value="">Toutes</option>
                                    <?php
                                    $stmt = $pdo->query("SELECT DISTINCT couleur FROM produit ORDER BY couleur");
                                    while ($row = $stmt->fetch()) {
                                        echo '<option value="' . htmlspecialchars($row['couleur']) . '">' . htmlspecialchars($row['couleur']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Statut</label>
                                <select class="form-select filter-select" name="statut">
                                    <option value="">Tous</option>
                                    <option value="en stock">En stock</option>
                                    <option value="en promotion">En promotion</option>
                                    <option value="rupture de stock">Rupture de stock</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Prix minimum</label>
                                <input type="number" class="form-control filter-input" name="prix_min" min="0" step="0.01" placeholder="Min">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Prix maximum</label>
                                <input type="number" class="form-control filter-input" name="prix_max" min="0" step="0.01" placeholder="Max">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Pointure</label>
                                <select class="form-select filter-select" name="pointure">
                                    <option value="">Toutes</option>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT DISTINCT p.id, p.pointure 
                                        FROM pointures p 
                                        INNER JOIN pointure_produit pp ON p.id = pp.id_pointure 
                                        ORDER BY p.pointure ASC
                                    ");
                                    while ($row = $stmt->fetch()) {
                                        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['pointure']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des produits -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Nom</th>
                                <th>Marque</th>
                                <th>Catégorie</th>
                                <th>Type</th>
                                <th>Prix</th>
                                <th>Quantité</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="10">
                                    <div class="text-center py-5">
                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucun produit trouvé</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($products as $product): ?>
                            <tr class="product-row" onclick="showProductDetails(<?php echo $product['id']; ?>)" data-couleur="<?php echo htmlspecialchars($product['couleur']); ?>">
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT URL_Image FROM images_produits WHERE id_produit = ? LIMIT 1");
                                    $stmt->execute([$product['id']]);
                                    $image = $stmt->fetchColumn();
                                    ?>
                                    <?php if ($image): ?>
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="Image produit" class="product-image">
                                    <?php else: ?>
                                        <div class="bg-secondary product-image d-flex align-items-center justify-content-center text-white">
                                            <i class="fas fa-image fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['nom']); ?></td>
                                <td><?php echo htmlspecialchars($product['marque']); ?></td>
                                <td><?php echo htmlspecialchars($product['catégorie'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($product['type']); ?></td>
                                <td><?php echo number_format($product['prix'], 2); ?> DT</td>
                                <td><?php echo htmlspecialchars($product['quantité']); ?></td>
                                <td>
                                    <span class="badge status-badge <?php 
                                        if ($product['statut'] === 'en stock') echo 'status-stock';
                                        else if ($product['statut'] === 'en promotion') echo 'status-promo';
                                        else if ($product['statut'] === 'rupture de stock') echo 'status-rupture';
                                        else echo 'bg-secondary';
                                    ?>">
                                        <?php echo ucfirst($product['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="loadProductDetails(<?php echo $product['id']; ?>); event.stopPropagation();" data-bs-toggle="modal" data-bs-target="#editProductModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm btn-delete-product" data-product-id="<?php echo $product['id']; ?>" onclick="event.stopPropagation();">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Produit -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom</label>
                                <input type="text" class="form-control" name="nom" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Marque</label>
                                <input type="text" class="form-control" name="marque" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Catégorie</label>
                                <select class="form-select" name="categorie" required>
                                    <option value="homme">Homme</option>
                                    <option value="femme">Femme</option>
                                    <option value="enfant">Enfant</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type" required>
                                    <option value="running">Running</option>
                                    <option value="casual">Casual</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Couleur</label>
                                <input type="text" class="form-control" name="couleur" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prix</label>
                                <input type="number" step="0.01" class="form-control" name="prix" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pointures et Stock</label>
                            <div class="row row-cols-3 g-2" id="addPointuresContainer">
                                <?php foreach ($pointures as $pointure): ?>
                                <div class="col">
                                    <div class="form-check form-check-inline bg-light p-2 rounded">
                                        <input class="form-check-input" type="checkbox" id="addPointure_<?php echo $pointure['id']; ?>" data-pointure-id="<?php echo $pointure['id']; ?>" value="<?php echo $pointure['pointure']; ?>">
                                        <label class="form-check-label" for="addPointure_<?php echo $pointure['id']; ?>">
                                            <?php echo htmlspecialchars($pointure['pointure']); ?>
                                        </label>
                                        <input type="number" class="form-control form-control-sm mt-1 add-pointure-stock" placeholder="Stock" style="display: none;" data-pointure-id="<?php echo $pointure['id']; ?>" min="0">
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantité totale</label>
                            <input type="number" class="form-control" name="quantite" id="add_total_quantite" readonly required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Statut</label>
                                <select class="form-select" name="statut" required>
                                    <option value="en stock">En stock</option>
                                    <option value="en promotion">En promotion</option>
                                    <option value="rupture de stock">Rupture de stock</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Promotion</label>
                                <select class="form-select" name="id_promotion">
                                    <option value="">Aucune promotion</option>
                                    <?php foreach ($promotions as $promotion): ?>
                                    <option value="<?php echo $promotion['id']; ?>">
                                        <?php echo htmlspecialchars($promotion['nom']); ?> (-<?php echo $promotion['discount']; ?>%)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Images</label>
                            <div class="camera-upload-wrapper">
                                <div id="add-images-preview" class="images-preview-container"></div>
                                <label for="add-images-input" class="camera-icon-label" title="Choisir des images">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3l2-3h6l2 3h3a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                </label>
                                <input type="file" class="custom-file-input" id="add-images-input" name="images[]" multiple accept="image/*">
                                <div class="camera-upload-text">Ajouter des images</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'édition -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nom</label>
                                    <input type="text" class="form-control" name="nom" id="edit_nom" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Marque</label>
                                    <input type="text" class="form-control" name="marque" id="edit_marque" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Catégorie</label>
                                    <select class="form-select" name="categorie" id="edit_categorie" required>
                                        <option value="homme">Homme</option>
                                        <option value="femme">Femme</option>
                                        <option value="enfant">Enfant</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="type" id="edit_type" required>
                                        <option value="running">Running</option>
                                        <option value="casual">Casual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Couleur</label>
                                    <input type="text" class="form-control" name="couleur" id="edit_couleur" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="edit_description" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Statut</label>
                                    <select class="form-select" name="statut" id="edit_statut" required>
                                        <option value="en stock">En stock</option>
                                        <option value="en promotion">En promotion</option>
                                        <option value="rupture de stock">Rupture de stock</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Promotion</label>
                                    <select class="form-select" name="id_promotion" id="edit_id_promotion">
                                        <option value="">Aucune promotion</option>
                                        <?php foreach ($promotions as $promotion): ?>
                                        <option value="<?php echo $promotion['id']; ?>">
                                            <?php echo htmlspecialchars($promotion['nom']); ?> (-<?php echo $promotion['discount']; ?>%)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Prix</label>
                                    <input type="number" class="form-control" name="prix" id="edit_prix" step="0.01" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Pointures et Stock</label>
                                    <div class="row row-cols-3 g-2" id="editPointuresContainer">
                                        <?php foreach ($pointures as $pointure): ?>
                                        <div class="col">
                                            <div class="form-check form-check-inline bg-light p-2 rounded">
                                                <input type="checkbox" class="form-check-input edit-pointure-checkbox" id="editPointure_<?php echo $pointure['id']; ?>" data-pointure-id="<?php echo $pointure['id']; ?>" value="<?php echo htmlspecialchars($pointure['pointure']); ?>">
                                                <label class="form-check-label" for="editPointure_<?php echo $pointure['id']; ?>">
                                                    <?php echo htmlspecialchars($pointure['pointure']); ?>
                                                </label>
                                                <input type="number" class="form-control form-control-sm mt-1 edit-pointure-stock" placeholder="Stock" style="display: none;" data-pointure-id="<?php echo $pointure['id']; ?>" min="0">
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Quantité totale</label>
                                    <input type="number" class="form-control" name="quantite" id="edit_total_quantite" readonly required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Images actuelles</label>
                            <div id="currentImages" class="images-preview-container"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nouvelles images</label>
                            <div class="camera-upload-wrapper">
                                <div id="edit-images-preview" class="images-preview-container"></div>
                                <label for="edit-images-input" class="camera-icon-label" title="Choisir des images">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3l2-3h6l2 3h3a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                </label>
                                <input type="file" class="custom-file-input" id="edit-images-input" name="images[]" multiple accept="image/*">
                                <div class="camera-upload-text">Ajouter des images</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="updateProduct()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails Produit -->
    <div class="modal fade product-details-modal" id="productDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails du Produit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-0">
                        <div class="col-md-6">
                            <div id="productDetailsCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner" id="productDetailsCarouselInner">
                                    <!-- Les images seront insérées ici par JavaScript -->
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#productDetailsCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon"></span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#productDetailsCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon"></span>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="product-info">
                                <h1 class="product-title" id="details_nom"></h1>
                                
                                <div class="price-tag" id="details_prix"></div>
                                
                                <div class="product-meta">
                                    <div class="meta-item">
                                        <div class="meta-label">Marque</div>
                                        <div class="meta-value" id="details_marque"></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Catégorie</div>
                                        <div class="meta-value" id="details_categorie"></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Type</div>
                                        <div class="meta-value" id="details_type"></div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-label">Couleur</div>
                                        <div class="meta-value" id="details_couleur"></div>
                                    </div>
                                </div>

                                <div class="product-description">
                                    <h5>Description</h5>
                                    <p id="details_description"></p>
                                </div>

                                <div class="sizes-section">
                                    <h5>Pointures disponibles</h5>
                                    <div class="sizes-container" id="details_pointures">
                                        <!-- Les pointures seront insérées ici par JavaScript -->
                                    </div>
                                </div>

                                <div class="status-section">
                                    <div class="meta-label">Statut</div>
                                    <div class="status-badge" id="details_statut"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fonction pour filtrer les produits
        function filterProducts() {
            const searchValue = document.getElementById('searchProduct').value.toLowerCase();
            const rows = document.querySelectorAll('.product-row');
            let visibleCount = 0;

            // Récupérer les valeurs des filtres
            const categorieFilter = document.querySelector('select[name="categorie"]').value;
            const typeFilter = document.querySelector('select[name="type"]').value;
            const marqueFilter = document.querySelector('select[name="marque"]').value;
            const couleurFilter = document.querySelector('select[name="couleur"]').value;
            const statutFilter = document.querySelector('select[name="statut"]').value;
            const pointureFilter = document.querySelector('select[name="pointure"]').value;
            const prixMin = document.querySelector('input[name="prix_min"]').value ? parseFloat(document.querySelector('input[name="prix_min"]').value) : 0;
            const prixMax = document.querySelector('input[name="prix_max"]').value ? parseFloat(document.querySelector('input[name="prix_max"]').value) : Infinity;

            // Fonction pour appliquer tous les filtres sauf pointure
            function applyFiltersExceptPointure() {
                rows.forEach(row => {
                    const nom = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                    const marque = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                    const categorie = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
                    const type = row.querySelector('td:nth-child(6)').textContent.toLowerCase();
                    const prix = parseFloat(row.querySelector('td:nth-child(7)').textContent);
                    const statut = row.querySelector('td:nth-child(9) .status-badge').textContent.trim().toLowerCase();
                    const couleur = row.getAttribute('data-couleur').toLowerCase();

                    const matchesFilters = 
                        (!categorieFilter || categorie === categorieFilter.toLowerCase()) &&
                        (!typeFilter || type === typeFilter.toLowerCase()) &&
                        (!marqueFilter || marque === marqueFilter.toLowerCase()) &&
                        (!couleurFilter || couleur === couleurFilter.toLowerCase()) &&
                        (!statutFilter || statut === statutFilter.toLowerCase()) &&
                        (prix >= prixMin && prix <= prixMax) &&
                        (nom.includes(searchValue) || marque.includes(searchValue) || categorie.includes(searchValue));

                    row.style.display = matchesFilters ? '' : 'none';
                });
            }

            // Appliquer d'abord tous les filtres sauf pointure
            applyFiltersExceptPointure();

            // Si un filtre de pointure est actif, l'appliquer
            if (pointureFilter) {
                fetch('get_all_products_pointures.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const productsWithPointure = {};
                            data.products.forEach(product => {
                                if (product.pointures_stocks.some(ps => ps.id === parseInt(pointureFilter))) {
                                    productsWithPointure[product.id] = true;
                                }
                            });

                            // Appliquer le filtre de pointure
                            rows.forEach(row => {
                                if (row.style.display !== 'none') {
                                    const productId = row.querySelector('td:first-child').textContent;
                                    if (!productsWithPointure[productId]) {
                                        row.style.display = 'none';
                                    }
                                }
                            });

                            // Mettre à jour le compteur
                            visibleCount = document.querySelectorAll('.product-row[style=""]').length;
                            document.getElementById('product-count').textContent = visibleCount;
                        }
                    });
            } else {
                // Si pas de filtre de pointure, mettre à jour le compteur
                visibleCount = document.querySelectorAll('.product-row[style=""]').length;
                document.getElementById('product-count').textContent = visibleCount;
            }
        }

        // Ajouter les écouteurs d'événements pour tous les filtres
        document.querySelectorAll('.filter-select, .filter-input').forEach(element => {
            element.addEventListener('change', filterProducts);
            element.addEventListener('input', filterProducts);
        });

        // Modifier l'écouteur d'événement de recherche existant
        document.getElementById('searchProduct').addEventListener('keyup', filterProducts);

        // Fonction pour sauvegarder un nouveau produit
        function saveProduct() {
            const form = document.getElementById('addProductForm');
            const formData = new FormData(form);

            // Récupérer les pointures et stocks sélectionnés
            const addPointuresContainer = document.getElementById('addPointuresContainer');
            if (addPointuresContainer) {
                addPointuresContainer.querySelectorAll('.form-check-input[type="checkbox"]:checked').forEach(checkbox => {
                    const pointureId = checkbox.dataset.pointureId;
                    const stockInput = addPointuresContainer.querySelector(`input.add-pointure-stock[data-pointure-id="${pointureId}"]`);
                    if (stockInput && stockInput.value !== '') {
                        formData.append('pointures_stocks[]', JSON.stringify({
                            pointure_id: pointureId,
                            stock: parseInt(stockInput.value)
                        }));
                    }
                });
            }

            fetch('add_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Succès!', 'Produit ajouté avec succès', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
            });
        }

        // Gestion des images pour l'ajout de produit
        document.addEventListener('DOMContentLoaded', function() {
            const addImagesInput = document.getElementById('add-images-input');
            const addImagesPreview = document.getElementById('add-images-preview');
            const addImagesLabel = document.querySelector('label[for="add-images-input"]');

            if (addImagesInput && addImagesLabel) {
                addImagesLabel.addEventListener('click', function(e) {
                    e.preventDefault();
                    addImagesInput.click();
                });

                addImagesInput.addEventListener('change', function() {
                    if (this.files) {
                        Array.from(this.files).forEach(file => {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const wrapper = document.createElement('div');
                                wrapper.className = 'image-preview-wrapper';
                                wrapper.innerHTML = `
                                    <img src="${e.target.result}" alt="Prévisualisation" class="image-preview">
                                    <span class="image-remove-x">&times;</span>
                                `;
                                addImagesPreview.appendChild(wrapper);

                                // Ajouter l'événement de suppression
                                const removeBtn = wrapper.querySelector('.image-remove-x');
                                removeBtn.addEventListener('click', function() {
                                    wrapper.remove();
                                    // Mettre à jour le FileList
                                    const dt = new DataTransfer();
                                    const files = addImagesInput.files;
                                    for (let i = 0; i < files.length; i++) {
                                        if (files[i] !== file) {
                                            dt.items.add(files[i]);
                                        }
                                    }
                                    addImagesInput.files = dt.files;
                                });
                            };
                            reader.readAsDataURL(file);
                        });
                    }
                });
            }
        });

        // Suppression d'un produit
        document.querySelectorAll('.btn-delete-product').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const productId = this.getAttribute('data-product-id');
                
                Swal.fire({
                    title: 'Supprimer ce produit ?',
                    text: 'Cette action est irréversible !',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('delete_product.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'id=' + encodeURIComponent(productId)
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Supprimé !', data.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Erreur', data.message, 'error');
                            }
                        })
                        .catch(() => {
                            Swal.fire('Erreur', 'Erreur lors de la communication avec le serveur.', 'error');
                        });
                    }
                });
            });
        });

        // Fonction pour charger les détails du produit
        function loadProductDetails(id) {
            fetch(`get_product.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.data;
                        document.getElementById('edit_id').value = product.id;
                        document.getElementById('edit_nom').value = product.nom;
                        document.getElementById('edit_marque').value = product.marque;
                        document.getElementById('edit_categorie').value = product.catégorie;
                        document.getElementById('edit_type').value = product.type;
                        document.getElementById('edit_couleur').value = product.couleur;
                        document.getElementById('edit_description').value = product.description;
                        document.getElementById('edit_statut').value = product.statut;
                        document.getElementById('edit_prix').value = product.prix;
                        document.getElementById('edit_total_quantite').value = product.quantité;
                        document.getElementById('edit_id_promotion').value = product.id_promotion || '';

                        // Pré-remplir les pointures et les stocks pour le formulaire d'édition
                        const editPointuresContainer = document.getElementById('editPointuresContainer');
                        if (editPointuresContainer) {
                            // Réinitialiser toutes les cases à cocher et champs de stock
                            editPointuresContainer.querySelectorAll('.edit-pointure-checkbox').forEach(checkbox => {
                                checkbox.checked = false;
                            });
                            editPointuresContainer.querySelectorAll('.edit-pointure-stock').forEach(stockInput => {
                                stockInput.value = '';
                                stockInput.style.display = 'none';
                            });

                            if (product.pointures_stocks && product.pointures_stocks.length > 0) {
                                product.pointures_stocks.forEach(ps => {
                                    const checkbox = editPointuresContainer.querySelector(`input[type="checkbox"][data-pointure-id="${ps.id}"]`);
                                    const stockInput = editPointuresContainer.querySelector(`input.edit-pointure-stock[data-pointure-id="${ps.id}"]`);
                                    if (checkbox) {
                                        checkbox.checked = true;
                                    }
                                    if (stockInput) {
                                        stockInput.value = ps.stock;
                                        stockInput.style.display = 'inline-block';
                                    }
                                });
                            }
                            // Mettre à jour la quantité totale après avoir pré-remi les stocks
                            calculateTotalQuantity('editPointuresContainer', 'edit_total_quantite');
                        }

                        // Afficher les images actuelles
                        const currentImagesDiv = document.getElementById('currentImages');
                        currentImagesDiv.innerHTML = '';
                        if (product.images && product.images.length > 0) {
                            product.images.forEach((image, index) => {
                                const wrapper = document.createElement('div');
                                wrapper.className = 'image-preview-wrapper';
                                wrapper.innerHTML = `
                                    <img src="${image}" alt="Prévisualisation" class="image-preview">
                                    <span class="image-remove-x" data-image-id="${product.image_ids[index]}">&times;</span>
                                `;
                                currentImagesDiv.appendChild(wrapper);

                                // Ajouter l'événement de suppression
                                const removeBtn = wrapper.querySelector('.image-remove-x');
                                removeBtn.addEventListener('click', function() {
                                    const imageId = this.getAttribute('data-image-id');
                                    const deleteImages = document.getElementById('delete_images') || document.createElement('input');
                                    deleteImages.type = 'hidden';
                                    deleteImages.id = 'delete_images';
                                    deleteImages.name = 'delete_images';

                                    let imagesToDelete = [];
                                    if (deleteImages.value) {
                                        imagesToDelete = JSON.parse(deleteImages.value);
                                    }
                                    imagesToDelete.push(imageId);
                                    deleteImages.value = JSON.stringify(imagesToDelete);

                                    if (!document.getElementById('delete_images')) {
                                        document.getElementById('editProductForm').appendChild(deleteImages);
                                    }

                                    wrapper.remove();
                                });
                            });
                        }
                    }
                });
        }

        // Gestion des images pour l'édition de produit
        document.addEventListener('DOMContentLoaded', function() {
            const editImagesInput = document.getElementById('edit-images-input');
            const editImagesPreview = document.getElementById('edit-images-preview');
            const editImagesLabel = document.querySelector('label[for="edit-images-input"]');

            if (editImagesInput && editImagesLabel) {
                editImagesLabel.addEventListener('click', function(e) {
                    e.preventDefault();
                    editImagesInput.click();
                });

                editImagesInput.addEventListener('change', function() {
                    // Vider la prévisualisation et la liste des fichiers actuels avant d'ajouter de nouvelles images
                    editImagesPreview.innerHTML = '';
                    // Remettre à zéro le FileList pour éviter la duplication des fichiers déjà traités
                    const dt = new DataTransfer(); 
                    
                    if (this.files) {
                        Array.from(this.files).forEach(file => {
                            dt.items.add(file); // Ajouter les nouveaux fichiers au FileList
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const wrapper = document.createElement('div');
                                wrapper.className = 'image-preview-wrapper';
                                wrapper.innerHTML = `
                                    <img src="${e.target.result}" alt="Prévisualisation" class="image-preview">
                                    <span class="image-remove-x">&times;</span>
                                `;
                                editImagesPreview.appendChild(wrapper);

                                // Ajouter l'événement de suppression
                                const removeBtn = wrapper.querySelector('.image-remove-x');
                                removeBtn.addEventListener('click', function() {
                                    wrapper.remove();
                                    // Mettre à jour le FileList après suppression
                                    const updatedDt = new DataTransfer();
                                    Array.from(editImagesInput.files).forEach(existingFile => {
                                        if (existingFile !== file) { // Comparer par référence d'objet
                                            updatedDt.items.add(existingFile);
                                        }
                                    });
                                    editImagesInput.files = updatedDt.files;
                                });
                            };
                            reader.readAsDataURL(file);
                        });
                        editImagesInput.files = dt.files; // Mettre à jour l'input avec le nouveau FileList
                    }
                });
            }
        });

        // Fonction pour mettre à jour le produit
        function updateProduct() {
            const form = document.getElementById('editProductForm');
            const formData = new FormData(form);

            // Récupérer les images à supprimer
            const deleteImagesInput = document.getElementById('delete_images');
            if (deleteImagesInput) {
                formData.append('delete_images', deleteImagesInput.value);
            }

            // Récupérer les pointures et stocks sélectionnés
            const editPointuresContainer = document.getElementById('editPointuresContainer');
            if (editPointuresContainer) {
                editPointuresContainer.querySelectorAll('.form-check-input.edit-pointure-checkbox:checked').forEach(checkbox => {
                    const pointureId = checkbox.dataset.pointureId;
                    const stockInput = editPointuresContainer.querySelector(`input.edit-pointure-stock[data-pointure-id="${pointureId}"]`);
                    if (stockInput && stockInput.value !== '') {
                        formData.append('pointures_stocks[]', JSON.stringify({
                            pointure_id: pointureId,
                            stock: parseInt(stockInput.value)
                        }));
                    }
                });
            }

            // Récupérer les nouvelles images
            const editImagesInput = document.getElementById('edit-images-input');
            if (editImagesInput && editImagesInput.files.length > 0) {
                // Vider d'abord les images existantes dans le FormData
                formData.delete('images[]');
                // Ajouter les nouvelles images
                Array.from(editImagesInput.files).forEach(file => {
                    formData.append('images[]', file);
                });
            }

            fetch('update_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Succès!',
                        text: 'Produit mis à jour avec succès',
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Réinitialiser l'input des images après la mise à jour réussie
                        editImagesInput.value = '';
                        document.getElementById('edit-images-preview').innerHTML = '';
                        location.reload();
                    });
                } else {
                    Swal.fire('Erreur', 'Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Erreur', 'Une erreur est survenue lors de la mise à jour du produit', 'error');
            });
        }

        // Fonction pour afficher les détails du produit dans la nouvelle modal
        function showProductDetails(id) {
            fetch(`get_product.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const product = data.data;

                        // Remplir les champs de la modal
                        document.getElementById('details_nom').textContent = product.nom;
                        document.getElementById('details_marque').textContent = product.marque;
                        document.getElementById('details_categorie').textContent = product.catégorie;
                        document.getElementById('details_type').textContent = product.type;
                        document.getElementById('details_couleur').textContent = product.couleur;
                        document.getElementById('details_prix').textContent = `${product.prix} DT`;
                        document.getElementById('details_description').textContent = product.description;

                        // Gérer le statut avec un style approprié
                        const statusBadge = document.getElementById('details_statut');
                        statusBadge.textContent = product.statut;
                        statusBadge.className = 'status-badge';
                        if (product.statut === 'en stock') {
                            statusBadge.style.backgroundColor = '#28a745';
                            statusBadge.style.color = 'white';
                        } else if (product.statut === 'en promotion') {
                            statusBadge.style.backgroundColor = '#ffc107';
                            statusBadge.style.color = 'black';
                        } else if (product.statut === 'rupture de stock') {
                            statusBadge.style.backgroundColor = '#dc3545';
                            statusBadge.style.color = 'white';
                        }

                        // Gérer l'affichage des pointures
                        const detailsPointuresContainer = document.getElementById('details_pointures');
                        detailsPointuresContainer.innerHTML = '';
                        if (product.pointures_stocks && product.pointures_stocks.length > 0) {
                            product.pointures_stocks.forEach(ps => {
                                const badge = document.createElement('div');
                                badge.className = 'size-badge';
                                badge.innerHTML = `
                                    <span>${ps.pointure}</span>
                                    <span class="stock">(${ps.stock} en stock)</span>
                                `;
                                detailsPointuresContainer.appendChild(badge);
                            });
                        } else {
                            detailsPointuresContainer.innerHTML = '<span class="text-muted">Aucune pointure disponible</span>';
                        }

                        // Gérer le carrousel d'images
                        const carouselInner = document.getElementById('productDetailsCarouselInner');
                        carouselInner.innerHTML = '';

                        if (product.images && product.images.length > 0) {
                            product.images.forEach((image, index) => {
                                const carouselItem = document.createElement('div');
                                carouselItem.className = `carousel-item ${index === 0 ? 'active' : ''}`;
                                carouselItem.innerHTML = `<img src="${image}" class="d-block w-100" alt="Image produit">`;
                                carouselInner.appendChild(carouselItem);
                            });
                        } else {
                            carouselInner.innerHTML = `
                                <div class="carousel-item active">
                                    <div class="d-flex align-items-center justify-content-center bg-light" style="height: 400px;">
                                        <i class="fas fa-image fa-5x text-muted"></i>
                                    </div>
                                </div>
                            `;
                        }

                        // Afficher la modal
                        const productDetailsModal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
                        productDetailsModal.show();
                    } else {
                        Swal.fire('Erreur', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Erreur', 'Une erreur est survenue lors de la récupération des détails du produit', 'error');
                });
        }

        // --- Fonctions pour la gestion des pointures (Ajout/Modification) ---

        function calculateTotalQuantity(containerId, totalQuantityFieldId) {
            let total = 0;
            // Déterminer la classe des champs de stock en fonction de l'ID du conteneur
            const stockInputClass = containerId === 'addPointuresContainer' ? 'add-pointure-stock' : 'edit-pointure-stock';
            const stockInputs = document.querySelectorAll(`#${containerId} .${stockInputClass}`);
            stockInputs.forEach(input => {
                if (input.style.display !== 'none' && input.value !== '') {
                    total += parseInt(input.value) || 0;
                }
            });
            document.getElementById(totalQuantityFieldId).value = total;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const addPointuresContainer = document.getElementById('addPointuresContainer');
            const addTotalQuantityField = document.getElementById('add_total_quantite');

            if (addPointuresContainer) {
                addPointuresContainer.addEventListener('change', function(event) {
                    const target = event.target;
                    if (target.type === 'checkbox') {
                        const stockInput = addPointuresContainer.querySelector(`input.add-pointure-stock[data-pointure-id="${target.dataset.pointureId}"]`);
                        if (stockInput) {
                            stockInput.style.display = target.checked ? 'inline-block' : 'none';
                            if (!target.checked) {
                                stockInput.value = ''; // Réinitialiser le stock si décoché
                            }
                            calculateTotalQuantity('addPointuresContainer', 'add_total_quantite');
                        }
                    } else if (target.classList.contains('add-pointure-stock')) {
                        calculateTotalQuantity('addPointuresContainer', 'add_total_quantite');
                    }
                });

                addPointuresContainer.addEventListener('input', function(event) {
                    const target = event.target;
                    if (target.classList.contains('add-pointure-stock')) {
                        calculateTotalQuantity('addPointuresContainer', 'add_total_quantite');
                    }
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const editPointuresContainer = document.getElementById('editPointuresContainer');
            const editTotalQuantityField = document.getElementById('edit_total_quantite');

            if (editPointuresContainer) {
                editPointuresContainer.addEventListener('change', function(event) {
                    const target = event.target;
                    if (target.type === 'checkbox') {
                        const stockInput = editPointuresContainer.querySelector(`input.edit-pointure-stock[data-pointure-id="${target.dataset.pointureId}"]`);
                        if (stockInput) {
                            stockInput.style.display = target.checked ? 'inline-block' : 'none';
                            if (!target.checked) {
                                stockInput.value = ''; // Réinitialiser le stock si décoché
                            }
                            calculateTotalQuantity('editPointuresContainer', 'edit_total_quantite');
                        }
                    } else if (target.classList.contains('edit-pointure-stock')) {
                        calculateTotalQuantity('editPointuresContainer', 'edit_total_quantite');
                    }
                });

                editPointuresContainer.addEventListener('input', function(event) {
                    const target = event.target;
                    if (target.classList.contains('edit-pointure-stock')) {
                        calculateTotalQuantity('editPointuresContainer', 'edit_total_quantite');
                    }
                });
            }
        });
    </script>
</body>
</html> 