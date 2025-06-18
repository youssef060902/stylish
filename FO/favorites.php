<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php include 'header.php'; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/all.min.css" rel="stylesheet">
  <link href="css/vendor.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <title>Mes favoris</title>
  <style>
    .favorite-title {
      font-size: 2rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 2rem;
      text-align: center;
    }
    .favorite-empty {
      text-align: center;
      color: #888;
      font-size: 1.2rem;
      margin: 3rem 0;
    }
    .product-card {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      overflow: hidden;
      transition: border-color 0.2s;
      background: #fff;
      height: 100%;
      display: flex;
      flex-direction: column;
      position: relative;
    }
    .product-card:hover {
      border-color: #e74c3c;
    }
    .card-img {
      position: relative;
      padding-top: 100%;
      overflow: hidden;
    }
    .product-image {
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;
    }
    .discount-badge {
      background-color: #ff4444;
      color: white;
      padding: 5px 10px;
      border-radius: 4px;
      font-weight: bold;
      font-size: 14px;
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 5;
    }
    .card-detail {
      padding: 15px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .card-title {
      font-size: 1.1em;
      margin-bottom: 5px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .card-title a {
      text-decoration: none;
      color: #2c3e50;
      font-weight: 600;
    }
    .card-title a:hover {
      color: #e74c3c;
    }
    .favorite-heart {
      cursor: pointer;
      transition: transform 0.15s;
      margin-left: 4px;
    }
    .favorite-heart:hover {
      transform: scale(1.15);
      filter: drop-shadow(0 2px 6px #e74c3c44);
    }
    .card-price {
      color: #e74c3c;
      font-size: 1.2em;
      font-weight: bold;
    }
    .original-price {
      font-size: 0.95em;
      color: #888;
      text-decoration: line-through;
      margin-left: 8px;
    }
    .fade-out {
      opacity: 0;
      transition: opacity 0.4s;
    }
  </style>
</head>
<body>
  <section class="container py-5">
    <h2 class="favorite-title">Mes favoris</h2>
    <?php if (!isset($_SESSION['user_id'])): ?>
      <div class="favorite-empty">Veuillez vous <a href="#" data-bs-toggle="modal" data-bs-target="#modallogin">connecter</a> pour voir vos favoris.</div>
    <?php else: ?>
      <?php
      $user_id = $_SESSION['user_id'];
      $host = 'localhost';
      $dbname = 'stylish';
      $username = 'root';
      $password = '';
      try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET NAMES utf8");
        $stmt = $pdo->prepare("SELECT p.*, pr.discount, pr.id AS promo_id FROM produit p JOIN favoris f ON p.id = f.id_produit LEFT JOIN promotion pr ON p.id_promotion = pr.id WHERE f.id_user = ?");
        $stmt->execute([$user_id]);
        $favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch(PDOException $e) {
        $favoris = [];
      }
      ?>
      <div id="favorites-list">
      <?php if (empty($favoris)): ?>
        <div class="favorite-empty">Vous n'avez aucun produit en favori.</div>
      <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
          <?php foreach ($favoris as $produit): ?>
            <?php
              // Chercher la première image du produit
              $imgStmt = $pdo->prepare("SELECT URL_Image FROM images_produits WHERE id_produit = ? LIMIT 1");
              $imgStmt->execute([$produit['id']]);
              $img = $imgStmt->fetch(PDO::FETCH_ASSOC);
              $imageUrl = $img && !empty($img['URL_Image']) ? $img['URL_Image'] : 'images/default_product.jpg';
              $isPromo = ($produit['statut'] === 'en promotion' && !empty($produit['discount']));
              if ($isPromo) {
                $prixPromo = $produit['prix'];
                $prixOriginal = round($produit['prix'] / (1 - $produit['discount']/100), 2);
              } else {
                $prixPromo = $produit['prix'];
                $prixOriginal = null;
              }
            ?>
            <div class="col favorite-card" data-produit-id="<?php echo $produit['id']; ?>">
              <div class="product-card h-100">
                <div class="card-img">
                  <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="<?php echo htmlspecialchars($produit['nom']); ?>" class="product-image">
                  <?php if ($isPromo): ?>
                    <span class="discount-badge">-<?php echo (int)$produit['discount']; ?>%</span>
                  <?php endif; ?>
                </div>
                <div class="card-detail">
                  <div class="d-flex align-items-center mb-2 card-title">
                    <a href="product-details.php?id=<?php echo $produit['id']; ?>">
                      <?php echo htmlspecialchars($produit['nom']); ?>
                    </a>
                    <svg class="favorite-heart" data-produit-id="<?php echo $produit['id']; ?>" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#e74c3c" stroke="#e74c3c" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:4px;cursor:pointer;">
                      <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                  </div>
                  <div class="mb-2">
                    <?php if ($isPromo): ?>
                      <span class="card-price"><?php echo number_format($prixPromo, 2, ',', ' '); ?> DT</span>
                      <span class="original-price"><?php echo number_format($prixOriginal, 2, ',', ' '); ?> DT</span>
                    <?php else: ?>
                      <span class="card-price"><?php echo number_format($prixPromo, 2, ',', ' '); ?> DT</span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>
  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/plugins.js"></script>
  <script src="js/script.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
 

    // Suppression AJAX du favori
    document.querySelectorAll('.favorite-heart').forEach(function(heart) {
      heart.addEventListener('click', function(e) {
        var produitId = this.getAttribute('data-produit-id');
        var card = this.closest('.favorite-card');
        fetch('remove_favorite.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'produit_id=' + encodeURIComponent(produitId)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            card.classList.add('fade-out');
            setTimeout(function() {
              card.remove();
              if (document.querySelectorAll('.favorite-card').length === 0) {
                document.getElementById('favorites-list').innerHTML = '<div class="favorite-empty">Vous n\'avez aucun produit en favori.</div>';
              }
            }, 400);
          } else {
            alert('Erreur lors de la suppression du favori.');
          }
        })
        .catch(() => {
          alert('Erreur lors de la communication avec le serveur.');
        });
      });
    });
  
  </script>
</body>
</html> 