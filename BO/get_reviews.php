<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_GET['product_id'])) {
    http_response_code(403);
    echo "Accès interdit ou ID de produit manquant.";
    exit();
}

$product_id = (int)$_GET['product_id'];
$focused_user_id = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Connexion à la base de données
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Avis pour ce produit
    $sql = "
        SELECT a.id, a.note, a.commentaire, a.date_creation, a.id_user,
               CONCAT(u.prenom, ' ', u.nom) as user_name, u.image as user_image
        FROM avis a
        JOIN user u ON a.id_user = u.id
        WHERE a.id_produit = :product_id
    ";

    $params = ['product_id' => $product_id];

    if ($focused_user_id) {
        $sql .= " ORDER BY CASE WHEN a.id_user = :user_id THEN 0 ELSE 1 END, a.date_creation DESC";
        $params['user_id'] = $focused_user_id;
    } else {
        $sql .= " ORDER BY a.date_creation DESC";
    }

    $stmt_reviews = $pdo->prepare($sql);
    $stmt_reviews->execute($params);
    $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    die("Erreur de base de données : " . $e->getMessage());
}

if (empty($reviews)) {
    echo '<p class="text-center text-muted">Aucun avis pour ce produit.</p>';
    exit();
}

// Rendu HTML pour la modale
?>
<div class="list-group">
    <?php foreach($reviews as $review): 
        $is_focused = $focused_user_id && $review['id_user'] == $focused_user_id;
        $item_class = 'list-group-item list-group-item-action';
        if ($is_focused) {
            $item_class .= ' bg-light'; // Léger surlignage pour l'avis focus
        }
    ?>
    <div class="<?php echo $item_class; ?>" id="review-<?php echo $review['id']; ?>">
        <div class="d-flex w-100 justify-content-between">
            <div class="d-flex align-items-center mb-2">
                <img src="<?php echo htmlspecialchars($review['user_image'] ?? 'https://via.placeholder.com/40'); ?>" 
                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" 
                     class="me-3" alt="Avatar">
                <div>
                    <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($review['date_creation'])); ?></small>
                </div>
            </div>
            <button class="btn btn-sm btn-outline-danger" onclick="deleteReview(<?php echo $review['id']; ?>)">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
        <div class="rating-stars mb-2" style="color: #ffc107;">
            <?php for($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star <?php echo ($i <= $review['note']) ? '' : 'text-muted'; ?>"></i>
            <?php endfor; ?>
        </div>
        <p class="mb-1"><?php echo nl2br(htmlspecialchars($review['commentaire'])); ?></p>
    </div>
    <?php endforeach; ?>
</div> 