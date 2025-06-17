<?php
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

header('Content-Type: application/json');

$products_per_page = 12;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $products_per_page;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET NAMES utf8");

    $conditions = ["p.id_promotion IS NOT NULL"];
    $params = [];
    $join_pointure_produit = false;

    if (isset($_GET['categories']) && !empty($_GET['categories'])) {
        $categories = $_GET['categories'];
        $placeholders = implode(',', array_fill(0, count($categories), '?'));
        $conditions[] = "p.catégorie IN ($placeholders)";
        $params = array_merge($params, $categories);
    }
    if (isset($_GET['types']) && !empty($_GET['types'])) {
        $types = $_GET['types'];
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $conditions[] = "p.type IN ($placeholders)";
        $params = array_merge($params, $types);
    }
    if (isset($_GET['colors']) && !empty($_GET['colors'])) {
        $colors = $_GET['colors'];
        $placeholders = implode(',', array_fill(0, count($colors), '?'));
        $conditions[] = "p.couleur IN ($placeholders)";
        $params = array_merge($params, $colors);
    }
    if (isset($_GET['brands']) && !empty($_GET['brands'])) {
        $brands = $_GET['brands'];
        $placeholders = implode(',', array_fill(0, count($brands), '?'));
        $conditions[] = "p.marque IN ($placeholders)";
        $params = array_merge($params, $brands);
    }
    if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
        $min_price = (float)$_GET['min_price'];
        $conditions[] = "p.prix >= ?";
        $params[] = $min_price;
    }
    if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
        $max_price = (float)$_GET['max_price'];
        $conditions[] = "p.prix <= ?";
        $params[] = $max_price;
    }
    if (isset($_GET['sizes']) && !empty($_GET['sizes'])) {
        $sizes = $_GET['sizes'];
        $placeholders = implode(',', array_fill(0, count($sizes), '?'));
        $conditions[] = "pp.id_pointure IN (SELECT id FROM pointures WHERE pointure IN ($placeholders))";
        $params = array_merge($params, $sizes);
        $join_pointure_produit = true;
    }

    // Compter le nombre total de produits en promotion
    $count_sql = "SELECT COUNT(DISTINCT p.id) FROM produit p";
    if ($join_pointure_produit) {
        $count_sql .= " JOIN pointure_produit pp ON p.id = pp.id_produit";
    }
    if (!empty($conditions)) {
        $count_sql .= " WHERE " . implode(' AND ', array_map(function($cond) { return str_replace('p.', '', $cond); }, $conditions));
    }
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetchColumn();
    $total_pages = ceil($total_products / $products_per_page);

    // Récupérer les produits en promotion filtrés avec pagination
    $sql = "SELECT DISTINCT p.id, p.nom, p.marque, p.catégorie, p.type, p.couleur, p.description, p.statut, p.prix, p.quantité, p.date_ajout, p.date_modification, p.id_promotion, pr.discount, pr.nom as promotion_nom,
                (SELECT URL_Image FROM images_produits WHERE id_produit = p.id LIMIT 1) as image_url
            FROM produit p
            LEFT JOIN promotion pr ON p.id_promotion = pr.id";
    if ($join_pointure_produit) {
        $sql .= " JOIN pointure_produit pp ON p.id = pp.id_produit";
    }
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " GROUP BY p.id ORDER BY p.date_ajout DESC LIMIT ? OFFSET ?";
    $params[] = (int)$products_per_page;
    $params[] = (int)$offset;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $filtered_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $products_html = '';
    if (empty($filtered_products)) {
        $products_html = '<div class="col-12"><p class="text-center text-muted">Aucun produit trouvé avec les filtres appliqués.</p></div>';
    } else {
        foreach ($filtered_products as $product) {
            $original_price = $product['prix'];
            if ($product['discount'] > 0) {
                $original_price = $product['prix'] / (1 - $product['discount'] / 100);
            }
            ob_start();
            ?>
            <div class="col mb-4"> 
              <div class="product-card position-relative">
                <div class="card-img" onclick="displayProductModal(<?php echo $product['id']; ?>)">
                  <?php if ($product['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="product-image img-fluid">
                  <?php else: ?>
                    <img src="images/no-image.jpg" alt="No image" class="product-image img-fluid">
                  <?php endif; ?>
                  <?php if ($product['discount'] > 0): ?>
                    <div class="discount-badge position-absolute top-0 end-0 m-2">
                      -<?php echo $product['discount']; ?>%
                    </div>
                  <?php endif; ?>
                </div>
                <div class="card-detail d-flex justify-content-between align-items-center mt-3">
                  <h3 class="card-title fs-6 fw-normal m-0">
                    <a href="product-details.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['nom']); ?></a>
                  </h3>
                  <div class="price-container">
                    <span class="card-price fw-bold"><?php echo number_format($product['prix'], 2); ?> DT</span>
                    <?php if ($product['discount'] > 0): ?>
                      <span class="original-price text-decoration-line-through text-muted ms-2"><?php echo number_format($original_price, 2); ?> DT</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php
            $products_html .= ob_get_clean();
        }
    }
    echo json_encode([
        'success' => true,
        'html' => $products_html,
        'total_pages' => (int)$total_pages,
        'current_page' => (int)$current_page
    ]);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du chargement des produits: ' . $e->getMessage(),
        'html' => '<div class="col-12"><p class="text-center text-danger">Erreur lors du chargement des produits.</p></div>'
    ]);
} 