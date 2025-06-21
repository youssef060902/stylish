<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Accès interdit');
}

// --- Connexion à la base de données ---
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die("Erreur de connexion : " . $e->getMessage());
}

// --- Construction de la requête dynamique ---
$base_query = "
    SELECT p.id, p.nom, COUNT(a.id) as review_count, AVG(a.note) as avg_rating,
           (SELECT URL_Image FROM images_produits ip WHERE ip.id_produit = p.id ORDER BY id ASC LIMIT 1) as product_image
    FROM produit p
    JOIN avis a ON p.id = a.id_produit
";
$params = [];
$having_clauses = [];

// Filtre par utilisateur
if (!empty($_GET['user_id'])) {
    $base_query .= " JOIN avis a_user ON p.id = a_user.id_produit AND a_user.id_user = :user_id ";
    $params[':user_id'] = (int)$_GET['user_id'];
}

// GROUP BY et HAVING pour le filtre par note
$sql = $base_query . " GROUP BY p.id, p.nom ";

if (!empty($_GET['rating'])) {
    $rating = (int)$_GET['rating'];
    $having_clauses[] = "FLOOR(AVG(a.note)) = :rating";
    $params[':rating'] = $rating;
}

if (!empty($having_clauses)) {
    $sql .= " HAVING " . implode(' AND ', $having_clauses);
}

$sql .= " ORDER BY review_count DESC";

// --- Exécution et rendu ---
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo '<tr><td colspan="4" class="text-center text-muted p-4">Aucun résultat trouvé pour ces filtres.</td></tr>';
    exit();
}

// Rendu du HTML pour les lignes du tableau
foreach($products as $p) {
    echo '<tr id="product-row-' . htmlspecialchars($p['id']) . '">';
    echo '<td>';
    echo '<div class="d-flex align-items-center">';
    echo '<img src="' . htmlspecialchars($p['product_image'] ?? 'https://via.placeholder.com/50') . '" class="product-image-sm me-3" alt="Produit">';
    echo '<span>' . htmlspecialchars($p['nom']) . '</span>';
    echo '</div>';
    echo '</td>';
    echo '<td class="text-center rating-stars">';
    echo '<strong>' . number_format($p['avg_rating'], 1) . '</strong> <i class="fas fa-star"></i>';
    echo '</td>';
    echo '<td class="text-center">';
    echo '<span class="badge bg-secondary review-count">' . htmlspecialchars($p['review_count']) . '</span>';
    echo '</td>';
    echo '<td class="text-center">';
    echo '<button class="btn btn-sm btn-outline-primary" onclick="showReviews(' . htmlspecialchars($p['id']) . ', \'' . htmlspecialchars(addslashes($p['nom'])) . '\')">';
    echo 'Voir les avis';
    echo '</button>';
    echo '</td>';
    echo '</tr>';
} 