<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$active_page = 'reviews';

// Connexion à la base de données
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vue liste des produits avec avis
$stmt_products = $pdo->query("
    SELECT p.id, p.nom, COUNT(a.id) as review_count, AVG(a.note) as avg_rating,
           (SELECT URL_Image FROM images_produits ip WHERE ip.id_produit = p.id ORDER BY id ASC LIMIT 1) as product_image
    FROM produit p
    JOIN avis a ON p.id = a.id_produit
    GROUP BY p.id, p.nom
    ORDER BY review_count DESC
");
$products_with_reviews = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les utilisateurs qui ont posté des avis pour le filtre
$stmt_users = $pdo->query("
    SELECT DISTINCT u.id, CONCAT(u.prenom, ' ', u.nom) as user_name
    FROM user u
    JOIN avis a ON u.id = a.id_user
    ORDER BY user_name ASC
");
$reviewing_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Avis - Stylish</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .product-image-sm { width: 50px; height: 50px; object-fit: cover; border-radius: 5px; }
        .rating-stars { color: #ffc107; }
        #reviewsModal .modal-body { max-height: 70vh; overflow-y: auto; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Gestion des Avis <span id="avis-count" class="badge bg-secondary ms-2"></span></h1>
            </div>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Filtres</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="userFilter" class="form-label">Filtrer par utilisateur</label>
                            <select id="userFilter" class="form-select filter-control">
                                <option value="">Tous les utilisateurs</option>
                                <?php foreach($reviewing_users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['user_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="ratingFilter" class="form-label">Filtrer par note</label>
                            <select id="ratingFilter" class="form-select filter-control">
                                <option value="">Toutes les notes</option>
                                <option value="5">Uniquement 5 étoiles</option>
                                <option value="4">4 étoiles (de 4 à 4.9)</option>
                                <option value="3">3 étoiles (de 3 à 3.9)</option>
                                <option value="2">2 étoiles (de 2 à 2.9)</option>
                                <option value="1">1 étoile (de 1 à 1.9)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Produit</th>
                            <th class="text-center">Note Moyenne</th>
                            <th class="text-center">Nombre d'Avis</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products_with_reviews as $p): ?>
                        <tr id="product-row-<?php echo $p['id']; ?>">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($p['product_image'] ?? 'https://via.placeholder.com/50'); ?>" class="product-image-sm me-3" alt="Produit">
                                    <span><?php echo htmlspecialchars($p['nom']); ?></span>
                                </div>
                            </td>
                            <td class="text-center rating-stars">
                                <strong><?php echo number_format($p['avg_rating'], 1); ?></strong> <i class="fas fa-star"></i>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary review-count"><?php echo $p['review_count']; ?></span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" onclick="showReviews(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['nom'])); ?>')">
                                    Voir les avis
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<!-- Modal pour les avis -->
<div class="modal fade" id="reviewsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewsModalTitle">Avis pour le produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reviewsModalBody">
                <!-- Contenu chargé via AJAX -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const reviewsModal = new bootstrap.Modal(document.getElementById('reviewsModal'));
    let currentProductId = null;

    function showReviews(productId, productName) {
        const focusedUserId = document.getElementById('userFilter').value;
        currentProductId = productId;
        const modalTitle = document.getElementById('reviewsModalTitle');
        const modalBody = document.getElementById('reviewsModalBody');
        
        modalTitle.textContent = `Avis pour "${productName}"`;
        modalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
        reviewsModal.show();

        let fetchUrl = `get_reviews.php?product_id=${productId}`;
        if (focusedUserId) {
            fetchUrl += `&user_id=${focusedUserId}`;
        }

        fetch(fetchUrl)
            .then(response => response.text())
            .then(html => {
                modalBody.innerHTML = html;
            })
            .catch(error => {
                modalBody.innerHTML = '<div class="alert alert-danger">Erreur de chargement des avis.</div>';
            });
    }

    function deleteReview(reviewId) {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cet avis ne pourra pas être récupéré.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer !',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('delete_review.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `review_id=${reviewId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`review-${reviewId}`).remove();
                        
                        // Mettre à jour le compteur sur la page principale
                        const productRow = document.getElementById(`product-row-${currentProductId}`);
                        if(productRow) {
                            const countSpan = productRow.querySelector('.review-count');
                            let currentCount = parseInt(countSpan.textContent);
                            currentCount--;
                            countSpan.textContent = currentCount;
                            if(currentCount === 0) {
                                productRow.remove(); // Optionnel: supprimer la ligne si plus d'avis
                            }
                        }

                        Swal.fire('Supprimé !', 'L\'avis a bien été supprimé.', 'success');
                    } else {
                        Swal.fire('Erreur', data.message, 'error');
                    }
                });
            }
        });
    }

    // Logique des filtres
    document.addEventListener('DOMContentLoaded', function() {
        updateAvisCount();
        document.querySelectorAll('.filter-control').forEach(el => {
            el.addEventListener('change', applyFilters);
        });
    });

    function applyFilters() {
        const userId = document.getElementById('userFilter').value;
        const rating = document.getElementById('ratingFilter').value;
        const tableBody = document.querySelector('.table tbody');
        
        tableBody.innerHTML = '<tr><td colspan="4" class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></td></tr>';

        const params = new URLSearchParams({ user_id: userId, rating: rating });

        fetch(`filter_reviews.php?${params}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('La réponse du serveur n\'est pas OK');
                }
                return response.text();
            })
            .then(html => {
                tableBody.innerHTML = html;
                updateAvisCount();
            })
            .catch(error => {
                console.error('Erreur lors du filtrage:', error);
                tableBody.innerHTML = '<tr><td colspan="4" class="text-center alert alert-danger">Erreur de chargement des résultats.</td></tr>';
            });
    }

    function updateAvisCount() {
        const rows = document.querySelectorAll('tbody tr[id^="product-row-"]');
        let visibleCount = 0;
        rows.forEach(row => {
            if (row.style.display !== 'none') visibleCount++;
        });
        document.getElementById('avis-count').textContent = visibleCount;
    }
</script>
</body>
</html> 