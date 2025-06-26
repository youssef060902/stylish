<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Définir la page active pour la sidebar
$active_page = 'favorites';

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

// --- Récupération des données pour les filtres ---

// Récupérer les utilisateurs qui ont des favoris
$users_with_favorites = $pdo->query("
    SELECT DISTINCT u.id, u.prenom, u.nom 
    FROM user u 
    JOIN favoris f ON u.id = f.id_user 
    ORDER BY u.prenom, u.nom
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les couleurs distinctes des produits favoris
$distinct_colors = $pdo->query("
    SELECT DISTINCT p.couleur 
    FROM produit p 
    JOIN favoris f ON p.id = f.id_produit 
    ORDER BY p.couleur
")->fetchAll(PDO::FETCH_ASSOC);

// --- Requête principale pour récupérer tous les favoris ---
$sql = "
    SELECT
        p.id, p.nom, p.marque, p.type, p.couleur, p.prix,
        (SELECT ip.URL_Image FROM images_produits ip WHERE ip.id_produit = p.id LIMIT 1) AS image,
        (SELECT COUNT(*) FROM favoris WHERE id_produit = p.id) AS total_favoris,
        GROUP_CONCAT(f.id_user) as favorited_by_users
    FROM produit p
    JOIN favoris f ON p.id = f.id_produit
    GROUP BY p.id
    ORDER BY total_favoris DESC
";
$stmt = $pdo->query($sql);
$favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favoris - Stylish Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .favoris-count {
            font-weight: bold;
            font-size: 1.1em;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Produits Favoris <span id="favoris-count" class="badge bg-secondary ms-2"></span></h2>
                    <div class="input-group" style="width: 350px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchProduct" class="form-control" placeholder="Rechercher par nom de produit...">
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filtres</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="user_id" class="form-label">Utilisateur</label>
                                <select id="user_id" class="form-select">
                                    <option value="">Tous les utilisateurs</option>
                                    <?php foreach ($users_with_favorites as $user): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="type" class="form-label">Type</label>
                                <select id="type" class="form-select">
                                    <option value="">Tous les types</option>
                                    <option value="running">Running</option>
                                    <option value="casual">Casual</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="couleur" class="form-label">Couleur</label>
                                <select id="couleur" class="form-select">
                                    <option value="">Toutes les couleurs</option>
                                    <?php foreach ($distinct_colors as $color): ?>
                                        <option value="<?php echo htmlspecialchars($color['couleur']); ?>">
                                            <?php echo htmlspecialchars($color['couleur']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des favoris -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Image</th>
                                <th>Nom du Produit</th>
                                <th>Marque</th>
                                <th>Type</th>
                                <th>Couleur</th>
                                <th>Prix</th>
                                <th class="text-center">Nb. Favoris <i class="fas fa-heart text-danger"></i></th>
                            </tr>
                        </thead>
                        <tbody id="favorisTableBody">
                            <?php if (empty($favoris)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-heart-crack fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucun produit favori trouvé avec les filtres actuels.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($favoris as $fav): ?>
                                    <tr class="product-row" 
                                        data-name="<?php echo htmlspecialchars(strtolower($fav['nom'])); ?>"
                                        data-type="<?php echo htmlspecialchars(strtolower($fav['type'])); ?>"
                                        data-color="<?php echo htmlspecialchars(strtolower($fav['couleur'])); ?>"
                                        data-users="<?php echo htmlspecialchars($fav['favorited_by_users']); ?>">
                                        <td>
                                            <img src="<?php echo htmlspecialchars($fav['image'] ?: 'path/to/default-image.jpg'); ?>" alt="<?php echo htmlspecialchars($fav['nom']); ?>" class="product-image">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($fav['nom']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($fav['marque']); ?></td>
                                        <td><?php echo htmlspecialchars($fav['type']); ?></td>
                                        <td><?php echo htmlspecialchars($fav['couleur']); ?></td>
                                        <td><?php echo htmlspecialchars($fav['prix']); ?> DT</td>
                                        <td class="text-center favoris-count">
                                            <?php echo $fav['total_favoris']; ?>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchProduct');
        const userFilter = document.getElementById('user_id');
        const typeFilter = document.getElementById('type');
        const colorFilter = document.getElementById('couleur');
        const tableBody = document.getElementById('favorisTableBody');
        const rows = tableBody.getElementsByClassName('product-row');
        const noResultsRow = tableBody.querySelector('td[colspan="7"]');

        function filterFavorites() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedUser = userFilter.value;
            const selectedType = typeFilter.value.toLowerCase();
            const selectedColor = colorFilter.value.toLowerCase();
            let visibleCount = 0;

            for (let row of rows) {
                const productName = row.getAttribute('data-name');
                const productType = row.getAttribute('data-type');
                const productColor = row.getAttribute('data-color');
                const favoritedByUsers = row.getAttribute('data-users').split(',');

                const matchesSearch = productName.includes(searchTerm);
                const matchesUser = !selectedUser || favoritedByUsers.includes(selectedUser);
                const matchesType = !selectedType || productType === selectedType;
                const matchesColor = !selectedColor || productColor === selectedColor;

                if (matchesSearch && matchesUser && matchesType && matchesColor) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
            
            if (noResultsRow) {
                noResultsRow.parentElement.style.display = (visibleCount === 0) ? '' : 'none';
            }

            updateFavorisCount();
        }

        function updateFavorisCount() {
            const rows = document.querySelectorAll('#favorisTableBody .product-row');
            let visibleCount = 0;
            rows.forEach(row => {
                if (row.style.display !== 'none') visibleCount++;
            });
            document.getElementById('favoris-count').textContent = visibleCount;
        }

        // Ajouter un écouteur d'événement pour chaque filtre
        searchInput.addEventListener('keyup', filterFavorites);
        userFilter.addEventListener('change', filterFavorites);
        typeFilter.addEventListener('change', filterFavorites);
        colorFilter.addEventListener('change', filterFavorites);
        
        // Appliquer les filtres au chargement initial si des valeurs sont déjà sélectionnées (par ex. cache du navigateur)
        filterFavorites();
        updateFavorisCount();
    });
    </script>
</body>
</html>
