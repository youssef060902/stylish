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

// Récupération des pointures disponibles
$stmt_sizes = $pdo->prepare("
    SELECT p.pointure 
    FROM pointure_produit pp
    JOIN pointures p ON pp.id_pointure = p.id
    WHERE pp.id_produit = ? AND pp.stock > 0
    ORDER BY p.pointure ASC
");
$stmt_sizes->execute([$productId]);
$sizes = $stmt_sizes->fetchAll(PDO::FETCH_COLUMN);

// Calcul du prix avec promotion
$prix_promo = $product['prix'];
$prix_promo = $product['prix'];
if ($product['discount'] > 0) {
    $prix_original = $prix_promo / (1 - $product['discount'] / 100);
    $prix_original = round($prix_original, 2);
}
?>
<?php include 'header.php'; ?>
<link rel="stylesheet" href="css/custom.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/vendor.css">
  <link rel="stylesheet" type="text/css" href="style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,900;1,900&family=Source+Sans+Pro:wght@400;600;700;900&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="css/all.min.css">

    <div class="container product-details">
        <div class="row">
            <div class="col-md-6">
                <div class="product-gallery">
                    <img src="<?php echo htmlspecialchars($images[0] ?? 'images/no-image.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($product['nom']); ?>" 
                         class="main-image" 
                         id="mainImage"
                         onclick="openLightbox(this.src)">
                    <div class="thumbnails">
                        <?php foreach ($images as $image): ?>
                        <img src="<?php echo htmlspecialchars($image); ?>" 
                             alt="Thumbnail" 
                             class="thumbnail"
                             onclick="changeMainImage(this.src); setActiveThumbnail(this);">
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
                        <?php if (!empty($sizes)): ?>
                        <div class="meta-item">
                            <div class="meta-label">Pointures disponibles</div>
                            <div class="meta-value">
                                <?php foreach ($sizes as $size): ?>
                                    <span class="badge bg-light text-dark border me-1 mb-1"><?php echo htmlspecialchars($size); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-description">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="addToCart(<?php echo $product['id']; ?>)">
                            <svg class="icon me-2" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>Ajouter au panier
                        </button>
                        <button class="btn btn-outline-danger" onclick="toggleFavorite(<?php echo $product['id']; ?>)">
                            <svg class="icon me-2" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21C12 21 4 13.36 4 8.5C4 5.42 6.42 3 9.5 3C11.24 3 12.91 3.81 14 5.08C15.09 3.81 16.76 3 18.5 3C21.58 3 24 5.42 24 8.5C24 13.36 16 21 16 21H12Z" stroke-linecap="round" stroke-linejoin="round"/></svg>Ajouter aux favoris
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
                    <?php if ($i <= $review['note']): ?>
                    <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="#ffc107" stroke="#ffc107" stroke-width="1"><polygon points="12,2 15,9 22,9.3 17,14.1 18.5,21 12,17.8 5.5,21 7,14.1 2,9.3 9,9"/></svg>
                    <?php else: ?>
                    <svg class="icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1"><polygon points="12,2 15,9 22,9.3 17,14.1 18.5,21 12,17.8 5.5,21 7,14.1 2,9.3 9,9"/></svg>
                    <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <div class="review-content">
                    <?php echo nl2br(htmlspecialchars($review['commentaire'])); ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <button class="btn btn-primary mt-4" data-bs-toggle="modal" data-bs-target="#addReviewModal">
                <svg class="icon me-2" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19.5 3 21l1.5-4L16.5 3.5z"/></svg>Ajouter un avis
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

    <!-- Lightbox pour la galerie d'images -->
    <div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
        <span class="lightbox-close" onclick="closeLightbox(event)">&times;</span>
        <img src="" alt="Agrandissement" id="lightbox-img">
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
        }
        function setActiveThumbnail(el) {
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
        }
        // Lightbox
        function openLightbox(src) {
            document.getElementById('lightbox-img').src = src;
            document.getElementById('lightbox').classList.add('active');
        }
        function closeLightbox(e) {
            if (e.target.classList.contains('lightbox') || e.target.classList.contains('lightbox-close')) {
                document.getElementById('lightbox').classList.remove('active');
            }
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