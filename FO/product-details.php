<?php
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

// Récupération de l'ID du produit
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId <= 0) {
    header('Location: index.php');
    exit();
}

// Récupération des détails du produit
$stmt = $pdo->prepare("SELECT p.*, pr.discount, pr.nom as promotion_nom
                       FROM produit p 
                       LEFT JOIN promotion pr ON p.id_promotion = pr.id 
                       WHERE p.id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit();
}

// Récupération des images du produit
$stmt_images = $pdo->prepare("SELECT URL_Image FROM images_produits WHERE id_produit = ? ORDER BY id ASC");
$stmt_images->execute([$productId]);
$images = $stmt_images->fetchAll(PDO::FETCH_COLUMN);

// Récupération des avis du produit
$stmt_reviews = $pdo->prepare("SELECT r.*, u.nom as user_nom, u.prenom as user_prenom 
                              FROM avis r 
                              LEFT JOIN user u ON r.id_user = u.id 
                              WHERE r.id_produit = ? 
                              ORDER BY r.date_creation DESC");
$stmt_reviews->execute([$productId]);
$reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

// Calcul du prix avec promotion
$prix_promo = $product['prix'];
$prix_promo = $product['prix'];
if ($product['discount'] > 0) {
    $prix_original = $prix_promo / (1 - $product['discount'] / 100);
    $prix_original = round($prix_original, 2);
}
?>
<?php include 'header.php'; ?>

    <div class="container product-details">
        <div class="row">
            <div class="col-md-6">
                <div class="product-gallery">
                    <img src="<?php echo htmlspecialchars($images[0] ?? 'images/no-image.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($product['nom']); ?>" 
                         class="main-image" 
                         id="mainImage">
                    <div class="thumbnails">
                        <?php foreach ($images as $image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>" 
                             alt="Thumbnail" 
                             class="thumbnail"
                             onclick="changeMainImage(this.src)">
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="product-info">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['nom']); ?></h1>
                    
                    <?php if ($product['discount'] > 0): ?>
                    <div class="promotion-badge">
                        <svg class="icon" style="width: 1em; height: 1em; fill: currentColor;">
                            <use xlink:href="#tag"></use>
                        </svg>
                        <span>Promotion -<?php echo $product['discount']; ?>%</span>
                    </div>
                    <?php endif; ?>

                    <div class="price-section">
                        <div class="price-tag">
                            <span class="current-price"><?php echo number_format($prix_promo, 2); ?> DT</span>
                            <?php if ($product['discount'] > 0): ?>
                            <span class="original-price"><?php echo number_format($prix_original, 2); ?> DT</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="product-meta">
                        <div class="meta-item">
                            <div class="meta-label">Marque</div>
                            <div class="meta-value"><?php echo htmlspecialchars($product['marque']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Catégorie</div>
                            <div class="meta-value"><?php echo htmlspecialchars($product['catégorie']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Type</div>
                            <div class="meta-value"><?php echo htmlspecialchars($product['type']); ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Couleur</div>
                            <div class="meta-value"><?php echo htmlspecialchars($product['couleur']); ?></div>
                        </div>
                    </div>

                    <div class="product-description">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="addToCart(<?php echo $product['id']; ?>)">
                            <i class="fas fa-shopping-cart me-2"></i>Ajouter au panier
                        </button>
                        <button class="btn btn-outline-danger" onclick="toggleFavorite(<?php echo $product['id']; ?>)">
                            <i class="fas fa-heart me-2"></i>Ajouter aux favoris
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="reviews-section">
            <h3 class="mb-4">Avis clients</h3>
            
            <?php if (empty($reviews)): ?>
            <div class="alert alert-info">
                Aucun avis pour ce produit. Soyez le premier à donner votre avis !
            </div>
            <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-author">
                        <?php echo htmlspecialchars($review['user_prenom'] . ' ' . $review['user_nom']); ?>
                    </div>
                    <div class="review-date">
                        <?php echo date('d/m/Y', strtotime($review['date_creation'])); ?>
                    </div>
                </div>
                <div class="review-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star<?php echo $i <= $review['note'] ? '' : '-o'; ?>"></i>
                    <?php endfor; ?>
                </div>
                <div class="review-content">
                    <?php echo nl2br(htmlspecialchars($review['commentaire'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <button class="btn btn-primary mt-4" data-bs-toggle="modal" data-bs-target="#addReviewModal">
                <i class="fas fa-pen me-2"></i>Ajouter un avis
            </button>
        </div>
    </div>

    <!-- Modal Ajout Avis -->
    <div class="modal fade" id="addReviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un avis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="reviewForm">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <div class="rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Commentaire</label>
                            <textarea class="form-control" name="comment" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="submitReview()">Publier</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
        }

        function addToCart(productId) {
            // Implémenter la logique d'ajout au panier
            alert('Produit ajouté au panier !');
        }

        function toggleFavorite(productId) {
            // Implémenter la logique d'ajout aux favoris
            alert('Produit ajouté aux favoris !');
        }

        function submitReview() {
            const form = document.getElementById('reviewForm');
            const formData = new FormData(form);

            fetch('add_review.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                alert('Une erreur est survenue');
            });
        }
    </script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Initialiser tous les dropdowns Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
});
</script>
</body>
</html> 