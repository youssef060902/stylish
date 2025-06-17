<?php
// Même logique de récupération des filtres que nouveautes.php, mais uniquement pour la catégorie 'homme'
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");

    // Récupérer les types uniques pour homme
    $stmt_types = $pdo->query("SELECT DISTINCT type FROM produit WHERE catégorie = 'homme' ORDER BY type");
    $types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);

    // Récupérer les couleurs uniques pour homme
    $stmt_colors = $pdo->query("SELECT DISTINCT couleur FROM produit WHERE catégorie = 'homme' ORDER BY couleur");
    $colors = $stmt_colors->fetchAll(PDO::FETCH_COLUMN);

    // Récupérer les marques uniques pour homme
    $stmt_brands = $pdo->query("SELECT DISTINCT marque FROM produit WHERE catégorie = 'homme' ORDER BY marque");
    $brands = $stmt_brands->fetchAll(PDO::FETCH_COLUMN);

    // Récupérer les pointures disponibles pour homme (jointure sur pointure_produit)
    $stmt_sizes = $pdo->query("SELECT DISTINCT p.pointure FROM pointures p JOIN pointure_produit pp ON p.id = pp.id_pointure JOIN produit pr ON pr.id = pp.id_produit WHERE pr.catégorie = 'homme' ORDER BY p.pointure");
    $sizes = $stmt_sizes->fetchAll(PDO::FETCH_COLUMN);

} catch(PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
    $types = [];
    $colors = [];
    $brands = [];
    $sizes = [];
}

// Juste avant le formulaire :
$categories = ['homme'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php include 'header.php'; ?>
  <title>Chaussures Hommes</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS (identique à nouveautes.php) -->
  <style>
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #e74c3c;
      --text-color: #2c3e50;
      --light-bg: #f8f9fa;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      --navbar-height: 80px;
    }
    body {
      font-family: 'Poppins', sans-serif;
      padding-top: var(--navbar-height);
    }
    .product-store .product-card {
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: none;
      transition: border-color 0.2s ease-in-out;
    }
    .product-store .product-card:hover {
      transform: none;
      border-color: var(--secondary-color);
      box-shadow: none;
    }
    .product-store .card-img {
      position: relative;
      padding-top: 100%;
      overflow: hidden;
    }
    .product-store .product-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .product-store .cart-concern {
      background: none;
      position: absolute;
      bottom: 0;
      padding: 10px;
      width: 100%;
      opacity: 0;
      transition: opacity 0.3s ease-out;
      pointer-events: none;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .product-store .product-card:hover .cart-concern {
      opacity: 1;
      pointer-events: all;
    }
    .product-store .cart-button {
        display: flex;
        gap: 10px;
        justify-content: center;
        width: 100%;
    }
    .product-store .cart-button .btn {
      background-color: transparent;
      color: var(--primary-color);
      border: 1px solid var(--primary-color);
      border-radius: 5px;
      padding: 8px 12px;
      font-size: 0.9em;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      transition: background-color 0.2s, color 0.2s, border-color 0.2s;
    }
    .product-store .cart-button .btn:hover {
      background-color: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }
    .product-store .cart-button svg {
      fill: currentColor;
      width: 18px;
      height: 18px;
    }
    .product-store .discount-badge {
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
    .product-store .card-detail {
      padding: 15px;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .product-store .card-title {
      font-size: 1.1em;
      margin-bottom: 5px;
    }
    .product-store .card-title a {
      text-decoration: none;
      color: var(--text-color);
    }
    .product-store .card-title a:hover {
      color: var(--secondary-color);
    }
    .product-store .price-container {
      display: flex;
      align-items: baseline;
      justify-content: flex-end;
      gap: 8px;
      margin-top: auto;
    }
    .product-store .card-price {
      color: var(--secondary-color);
      font-size: 1.2em;
      font-weight: bold;
    }
    .product-store .original-price {
      font-size: 0.9em;
      color: #888;
      text-decoration: line-through;
    }
    .product-store .row {
      display: flex;
      margin: 0 -10px;
      padding: 10px 0;
    }
    .product-store .col {
      padding: 0 10px;
    }
    main {
      position: relative;
      z-index: 1;
    }
    /* Styles pour la modal de détails du produit (copié de nouveautes.php) */
    .product-details-modal .modal-content {
      border: none;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    .product-details-modal .modal-header {
      border-bottom: none;
      padding: 1.5rem;
      background-color: #fff;
    }
    .product-details-modal .modal-title {
      font-weight: 600;
      color: #2c3e50;
      font-size: 1.5rem;
    }
    .product-details-modal .modal-body {
      padding: 0;
    }
    .product-details-modal .carousel {
      border-radius: 0;
      overflow: hidden;
    }
    .product-details-modal .carousel-item img {
      height: 500px;
      object-fit: contain;
      background-color: #f8f9fa;
    }
    .product-details-modal .carousel-control-prev,
    .product-details-modal .carousel-control-next {
      width: 40px;
      height: 40px;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 50%;
      top: 50%;
      transform: translateY(-50%);
      margin: 0 20px;
    }
    .product-details-modal .carousel-control-prev-icon,
    .product-details-modal .carousel-control-next-icon {
      width: 20px;
      height: 20px;
    }
    .product-details-modal .product-info {
      padding: 2.5rem;
      background: #fff;
    }
    .product-details-modal .product-title {
      font-size: 2rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .product-details-modal .promotion-badge {
      background-color: #e74c3c;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    .product-details-modal .promotion-badge svg {
      width: 0.8em;
      height: 0.8em;
      vertical-align: middle;
    }
    .product-details-modal .price-tag {
      display: flex;
      align-items: baseline;
      gap: 15px;
      margin-bottom: 2rem;
    }
    .product-details-modal .price-tag span {
      font-size: 2rem;
      font-weight: 700;
      color: #e74c3c;
    }
    .product-details-modal .original-price-tag span {
      font-size: 1.2rem;
      color: #95a5a6;
      text-decoration: line-through;
    }
    .product-details-modal .product-meta {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
      margin-bottom: 2rem;
    }
    .product-details-modal .meta-item {
      background-color: #f8f9fa;
      padding: 1rem;
      border-radius: 12px;
      transition: all 0.3s ease;
    }
    .product-details-modal .meta-item:hover {
      background-color: #e9ecef;
      transform: translateY(-2px);
    }
    .product-details-modal .meta-label {
      font-size: 0.9rem;
      color: #7f8c8d;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .product-details-modal .meta-value {
      font-weight: 600;
      color: #2c3e50;
      font-size: 1.1rem;
    }
    .product-details-modal .product-description {
      background-color: #f8f9fa;
      padding: 1.5rem;
      border-radius: 12px;
      margin-bottom: 2rem;
    }
    .product-details-modal .product-description h5 {
      color: #2c3e50;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    .product-details-modal .product-description p {
      color: #34495e;
      line-height: 1.6;
      margin: 0;
    }
    .product-details-modal .sizes-section h5 {
      color: #2c3e50;
      font-weight: 600;
      margin-bottom: 1rem;
    }
    .product-details-modal .sizes-container {
      display: flex;
      flex-wrap: wrap;
      gap: 0.8rem;
    }
    .product-details-modal .size-badge {
      background-color: #f8f9fa;
      color: #2c3e50;
      padding: 0.8rem 1.5rem;
      border-radius: 30px;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      cursor: pointer;
      border: 2px solid transparent;
    }
    .product-details-modal .size-badge:hover {
      background-color: #e9ecef;
      transform: translateY(-2px);
    }
    .product-details-modal .status-section {
      margin-top: 2rem;
    }
    .product-details-modal .status-badge {
      display: inline-block;
      padding: 0.8rem 1.5rem;
      border-radius: 30px;
      font-weight: 600;
      font-size: 1rem;
    }
    .product-details-modal .btn-primary {
      background-color: #e74c3c;
      border-color: #e74c3c;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 30px;
      transition: all 0.3s ease;
    }
    .product-details-modal .btn-primary:hover {
      background-color: #c0392b;
      border-color: #c0392b;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
    }
    .product-details-modal .btn-favorite {
      background-color: #ffffff;
      border: 2px solid #e74c3c;
      color: #e74c3c;
      padding: 1rem 1.5rem;
      font-size: 1.1rem;
      font-weight: 600;
      border-radius: 30px;
      transition: all 0.3s ease;
    }
    .product-details-modal .btn-favorite:hover {
      background-color: #e74c3c;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(231, 76, 60, 0.2);
    }
    .product-details-modal .btn-favorite svg,
    .product-details-modal .btn-primary svg {
      width: 1.1em;
      height: 1.1em;
      vertical-align: middle;
    }
    .product-details-modal .btn-close {
      position: absolute;
      right: 1.5rem;
      top: 1.5rem;
      background-color: #f8f9fa;
      padding: 0.8rem;
      border-radius: 50%;
      opacity: 1;
      transition: all 0.3s ease;
    }
    .product-details-modal .btn-close:hover {
      background-color: #e9ecef;
      transform: rotate(90deg);
    }
  </style>

<link rel="stylesheet" href="css/vendor.css">
  <link rel="stylesheet" type="text/css" href="style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,900;1,900&family=Source+Sans+Pro:wght@400;600;700;900&display=swap"
    rel="stylesheet">
  <style>
    
  </style>
  <link rel="stylesheet" href="css/all.min.css"
</head>
<body>
<?php /* SVG caché pour les icônes, structure, modale, etc. identiques à nouveautes.php */ ?>
<!-- Copie conforme de la structure HTML de nouveautes.php, sauf titre et endpoint AJAX -->
<!-- SVG caché -->
<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
  <symbol id="heart" viewBox="0 0 24 24">
    <path d="M12 21.35l-1.84-1.66C4.01 15.36 2 13.06 2 10.11 2 6.7 4.7 4 8.11 4c1.98 0 3.91.96 5.12 2.5l.77.93.77-.93C15.9 4.96 17.82 4 19.89 4 23.3 4 26 6.7 26 10.11c0 2.95-2.01 5.25-8.16 9.58L12 21.35z"/>
  </symbol>
  <symbol id="heart-outline" viewBox="0 0 24 24">
    <path d="M16.5 3C14.77 3 13.1 3.81 12 5.09 10.9 3.81 9.23 3 7.5 3 4.42 3 2 5.42 2 8.5c0 3.78 3.4 6.86 8.55 11.54L12 21.35l1.45-1.31C18.6 15.36 22 12.28 22 8.5 22 5.42 19.58 3 16.5 3zm-4.4 15.71l-.1-.09-.1-.09c-4.9-4.47-7.9-7.14-7.9-10.03 0-2.31 1.76-4.17 3.93-4.17 1.81 0 3.42 1.05 4.17 2.68.75-1.63 2.36-2.68 4.17-2.68 2.17 0 3.93 1.86 3.93 4.17 0 2.89-3 5.56-7.9 10.03l-.1.09-.1.09-.1.09-.1.09z"/>
  </symbol>
  <symbol id="tag" viewBox="0 0 24 24">
    <path d="M20 12l-1.41-1.41L12 17.17l-6.59-6.58L4 12l8 8 8-8zM12 4l-8 8 8 8 8-8-8-8z"/>
  </symbol>
</svg>
<section id="promotions-products" class="product-store py-2 my-2 py-md-5 my-md-5 pt-0">
  <div class="container-md">
    <div class="display-header d-flex align-items-center justify-content-center">
      <h2 class="section-title-center text-uppercase">Liste des chaussures hommes</h2>
    </div>
    <div class="row">
      <div class="col-md-3">
        <div class="filter-sidebar">
          <h4 class="mb-3">Filtres</h4>
          <form id="filterForm">
            <!-- Types -->
            <div class="mb-3">
              <h5>Type</h5>
              <div id="type-filters">
                <?php foreach ($types as $type): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($type); ?>" id="type_<?php echo htmlspecialchars($type); ?>" name="types[]">
                    <label class="form-check-label" for="type_<?php echo htmlspecialchars($type); ?>">
                      <?php echo htmlspecialchars($type); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <!-- Couleurs -->
            <div class="mb-3">
              <h5>Couleur</h5>
              <div id="color-filters">
                <?php foreach ($colors as $color): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($color); ?>" id="color_<?php echo htmlspecialchars($color); ?>" name="colors[]">
                    <label class="form-check-label" for="color_<?php echo htmlspecialchars($color); ?>">
                      <?php echo htmlspecialchars($color); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <!-- Marques -->
            <div class="mb-3">
              <h5>Marque</h5>
              <div id="brand-filters">
                <?php foreach ($brands as $brand): ?>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($brand); ?>" id="brand_<?php echo htmlspecialchars($brand); ?>" name="brands[]">
                    <label class="form-check-label" for="brand_<?php echo htmlspecialchars($brand); ?>">
                      <?php echo htmlspecialchars($brand); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <!-- Prix -->
            <div class="mb-3">
              <h5>Prix (DT)</h5>
              <div class="d-flex gap-2">
                <input type="number" class="form-control form-control-sm" id="min-price" placeholder="Min" step="0.01" name="min_price" min=0>
                <input type="number" class="form-control form-control-sm" id="max-price" placeholder="Max" step="0.01" name="max_price" min=0>
              </div>
            </div>
            <!-- Pointures -->
            <div class="mb-3">
              <h5>Pointures</h5>
              <div id="sizes-filters" class="d-flex flex-wrap gap-2">
                <?php foreach ($sizes as $size): ?>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" value="<?php echo htmlspecialchars($size); ?>" id="size_<?php echo htmlspecialchars($size); ?>" name="sizes[]">
                    <label class="form-check-label" for="size_<?php echo htmlspecialchars($size); ?>">
                      <?php echo htmlspecialchars($size); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Appliquer les filtres</button>
          </form>
        </div>
      </div>
      <div class="col-md-9">
        <div class="product-content padding-small" id="product-list-container">
          <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4">
            <!-- Les produits seront chargés ici par AJAX -->
          </div>
        </div>
        <!-- Pagination Controls -->
        <nav aria-label="Page navigation">
          <ul class="pagination justify-content-center" id="pagination-controls">
            <!-- Pagination links will be generated here by JavaScript -->
          </ul>
        </nav>
      </div>
    </div>
  </div>
</section>
<!-- Modale de détail produit, scripts, etc. identiques à nouveautes.php -->
<!-- ... (copier/coller la modale, les scripts JS, etc. de nouveautes.php, en changeant juste le endpoint AJAX) ... -->
<?php include 'footer.php'; ?>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/jquery-1.11.0.min.js"></script>
<script src="js/plugins.js"></script>
<script src="js/script.js"></script>
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
  let currentProductId = null;
  let selectedSize = null;
  let showModalTimeout = null;
  let hideModalTimeout = null;
  let productDetailsModalInstance = null;
  let currentPage = 1; // Current page for pagination

  function getCurrentProductId() {
      return currentProductId;
  }

  // Fonction pour charger les produits filtrés via AJAX
  function loadFilteredProducts() {
      const form = document.getElementById('filterForm');
      const formData = new FormData(form);
      const params = new URLSearchParams();

      for (const pair of formData.entries()) {
          if (pair[0].endsWith('[]')) {
              params.append(pair[0], pair[1]);
          } else {
              params.set(pair[0], pair[1]);
          }
      }
      params.set('page', currentPage);

      fetch(`get_filtered_hommes.php?${params.toString()}`)
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  document.getElementById('product-list-container').innerHTML = `<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4">${data.html}</div>`;
                  renderPaginationControls(data.total_pages, data.current_page);
              } else {
                  console.error('Erreur lors du chargement des produits filtrés:', data.message);
                  document.getElementById('product-list-container').innerHTML = data.html;
                  document.getElementById('pagination-controls').innerHTML = '';
              }
          })
          .catch(error => {
              console.error('Erreur lors du chargement des produits filtrés:', error);
              document.getElementById('product-list-container').innerHTML = '<div class="col-12"><p class="text-center text-danger">Erreur lors du chargement des produits.</p></div>';
              document.getElementById('pagination-controls').innerHTML = '';
          });
  }

  function renderPaginationControls(totalPages, currentPage) {
      const paginationContainer = document.getElementById('pagination-controls');
      paginationContainer.innerHTML = '';
      if (totalPages < 1) {
          return;
      }
      const prevClass = currentPage === 1 ? 'disabled' : '';
      paginationContainer.innerHTML += `
          <li class="page-item ${prevClass}">
              <a class="page-link" href="#" data-page="${currentPage - 1}">Précédent</a>
          </li>
      `;
      for (let i = 1; i <= totalPages; i++) {
          const activeClass = i === currentPage ? 'active' : '';
          paginationContainer.innerHTML += `
              <li class="page-item ${activeClass}">
                  <a class="page-link" href="#" data-page="${i}">${i}</a>
              </li>
          `;
      }
      const nextClass = currentPage === totalPages ? 'disabled' : '';
      paginationContainer.innerHTML += `
          <li class="page-item ${nextClass}">
              <a class="page-link" href="#" data-page="${currentPage + 1}">Suivant</a>
          </li>
      `;
      paginationContainer.querySelectorAll('.page-link').forEach(link => {
          link.addEventListener('click', function(e) {
              e.preventDefault();
              const newPage = parseInt(this.dataset.page);
              if (newPage >= 1 && newPage <= totalPages) {
                  currentPage = newPage;
                  loadFilteredProducts();
              }
          });
      });
  }

  document.getElementById('filterForm').addEventListener('submit', function(event) {
      event.preventDefault();
      const minPrice = parseFloat(document.getElementById('min-price').value);
      const maxPrice = parseFloat(document.getElementById('max-price').value);
      if (!isNaN(minPrice) && !isNaN(maxPrice) && minPrice > maxPrice) {
          alert('Le prix minimum doit être inférieur ou égal au prix maximum.');
          return;
      }
      currentPage = 1;
      loadFilteredProducts();
  });

  document.addEventListener('DOMContentLoaded', function() {
      loadFilteredProducts();
      var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
      var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
          return new bootstrap.Dropdown(dropdownToggleEl);
      });
      const modalElement = document.getElementById('productDetailsModal');
      productDetailsModalInstance = new bootstrap.Modal(modalElement, {
          keyboard: true,
          focus: true
      });
      modalElement.addEventListener('mouseleave', () => {
          if (showModalTimeout) {
              clearTimeout(showModalTimeout);
              showModalTimeout = null;
          }
          if (hideModalTimeout) {
              clearTimeout(hideModalTimeout);
          }
          if (productDetailsModalInstance) {
              productDetailsModalInstance.hide();
          }
      });
      modalElement.addEventListener('mouseenter', () => {
          if (hideModalTimeout) {
              clearTimeout(hideModalTimeout);
              hideModalTimeout = null;
          }
      });
  });

  function displayProductModal(id) {
      if (hideModalTimeout) {
          clearTimeout(hideModalTimeout);
          hideModalTimeout = null;
      }
      if (productDetailsModalInstance && productDetailsModalInstance._isShown && currentProductId === id) {
          return;
      }
      if (showModalTimeout) {
          clearTimeout(showModalTimeout);
      }
      currentProductId = id;
      selectedSize = null;
      showModalTimeout = setTimeout(() => {
          fetch(`get_product_details.php?id=${id}`)
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      const product = data.data;
                      document.getElementById('details_nom').textContent = product.nom;
                      document.getElementById('details_marque').textContent = product.marque;
                      document.getElementById('details_categorie').textContent = product.catégorie;
                      document.getElementById('details_type').textContent = product.type;
                      document.getElementById('details_couleur').textContent = product.couleur;
                      document.getElementById('details_description').textContent = product.description;
                      const prixPromo = parseFloat(product.prix);
                      const discount = parseFloat(product.discount);
                      document.getElementById('details_prix_promo').textContent = `${prixPromo.toFixed(2)} DT`;
                      if (discount > 0) {
                          const prixOriginal = prixPromo / (1 - discount / 100);
                          document.getElementById('details_promotion_badge').style.display = 'inline-flex';
                          document.getElementById('details_prix_original').textContent = `${prixOriginal.toFixed(2)} DT`;
                          document.getElementById('details_prix_original').style.display = 'inline';
                      } else {
                          document.getElementById('details_promotion_badge').style.display = 'none';
                          document.getElementById('details_prix_original').textContent = '';
                          document.getElementById('details_prix_original').style.display = 'none';
                      }
                      const carouselInner = document.getElementById('productDetailsCarouselInner');
                      carouselInner.innerHTML = '';
                      if (product.images && product.images.length > 0) {
                          product.images.forEach((image, index) => {
                              const carouselItem = document.createElement('div');
                              carouselItem.className = `carousel-item ${index === 0 ? 'active' : ''}`;
                              carouselItem.innerHTML = `
                                  <img src="${image}" class="d-block w-100" alt="${product.nom}">
                              `;
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
                      const sizesContainer = document.getElementById('details_pointures');
                      sizesContainer.innerHTML = '';
                      if (product.pointures && product.pointures.length > 0) {
                          product.pointures.forEach(pointure => {
                              const sizeBadge = document.createElement('div');
                              sizeBadge.className = 'size-badge';
                              sizeBadge.innerHTML = `<span>${pointure}</span>`;
                              sizeBadge.onclick = () => selectSize(sizeBadge, pointure);
                              sizesContainer.appendChild(sizeBadge);
                          });
                      } else {
                          sizesContainer.innerHTML = '<span class="text-muted">Aucune pointure disponible</span>';
                      }
                      checkFavoriteStatus(id);
                      const modalElement = document.getElementById('productDetailsModal');
                      productDetailsModalInstance = new bootstrap.Modal(modalElement);
                      productDetailsModalInstance.show();
                  } else {
                      console.error('Erreur lors du chargement des détails du produit:', data.message);
                  }
              })
              .catch(error => {
                  console.error('Erreur:', error);
              });
      });
  }

  function hideProductModal() {
      if (showModalTimeout) {
          clearTimeout(showModalTimeout);
          showModalTimeout = null;
      }
      if (productDetailsModalInstance) {
          productDetailsModalInstance.hide();
      }
  }

  function selectSize(sizeBadge, pointure) {
      document.querySelectorAll('.size-badge').forEach(badge => {
          badge.classList.remove('selected');
      });
      sizeBadge.classList.add('selected');
      selectedSize = pointure;
  }

  function addToCart(productId) {
      if (!selectedSize) {
          alert('Veuillez sélectionner une pointure');
          return;
      }
      fetch('add_to_cart.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              productId: productId,
              size: selectedSize,
              quantity: 1
          })
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              updateCartCount();
              alert('Produit ajouté au panier avec succès');
          } else {
              alert(data.message || 'Erreur lors de l\'ajout au panier');
          }
      })
      .catch(error => {
          console.error('Erreur:', error);
          alert('Erreur lors de l\'ajout au panier');
      });
  }

  function toggleFavorite(productId) {
      fetch('toggle_favorite.php', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              productId: productId
          })
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              const favoriteSvgUse = document.querySelector('.btn-outline-danger svg use');
              if (favoriteSvgUse) {
                  if (data.isFavorite) {
                      favoriteSvgUse.setAttribute('xlink:href', '#heart');
                  } else {
                      favoriteSvgUse.setAttribute('xlink:href', '#heart-outline');
                  }
              }
          }
      })
      .catch(error => {
          console.error('Erreur:', error);
      });
  }

  function checkFavoriteStatus(productId) {
      fetch(`check_favorite.php?id=${productId}`)
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  const favoriteSvgUse = document.querySelector('.btn-outline-danger svg use');
                  if (favoriteSvgUse) {
                      if (data.isFavorite) {
                          favoriteSvgUse.setAttribute('xlink:href', '#heart');
                      } else {
                          favoriteSvgUse.setAttribute('xlink:href', '#heart-outline');
                      }
                  }
              }
          })
          .catch(error => {
              console.error('Erreur:', error);
          });
  }

  function updateCartCount() {
      fetch('get_cart_count.php')
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  const cartCount = document.getElementById('cartCount');
                  if (cartCount) {
                      cartCount.textContent = data.count;
                  }
              }
          })
          .catch(error => {
              console.error('Erreur:', error);
          });
  }
</script>

<!-- Modal des détails du produit (identique à nouveautes.php) -->
<div class="modal fade product-details-modal" id="productDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
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
                        <div class="product-info p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h1 class="product-title mb-0" id="details_nom"></h1>
                                <div id="details_promotion_badge" class="promotion-badge" style="display: none;">
                                    <svg class="icon" style="width: 1em; height: 1em; fill: currentColor;"><use xlink:href="#tag"></use></svg>
                                    <span>Promotion</span>
                                </div>
                            </div>
                            <div class="price-section mb-4">
                                <div class="price-tag d-flex align-items-baseline gap-2">
                                    <span id="details_prix_promo" class="text-danger fw-bold fs-3"></span>
                                    <span id="details_prix_original" class="text-muted text-decoration-line-through"></span>
                                </div>
                            </div>
                            <div class="product-meta mb-4">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="meta-item p-3 rounded-3 bg-light">
                                            <div class="meta-label text-muted small">Marque</div>
                                            <div class="meta-value fw-semibold" id="details_marque"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="meta-item p-3 rounded-3 bg-light">
                                            <div class="meta-label text-muted small">Catégorie</div>
                                            <div class="meta-value fw-semibold" id="details_categorie"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="meta-item p-3 rounded-3 bg-light">
                                            <div class="meta-label text-muted small">Type</div>
                                            <div class="meta-value fw-semibold" id="details_type"></div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="meta-item p-3 rounded-3 bg-light">
                                            <div class="meta-label text-muted small">Couleur</div>
                                            <div class="meta-value fw-semibold" id="details_couleur"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="product-description bg-light p-4 rounded-3 mb-4">
                                <h5 class="fw-semibold mb-3">Description</h5>
                                <p id="details_description" class="mb-0"></p>
                            </div>
                            <div class="sizes-section mb-4">
                                <h5 class="fw-semibold mb-3">Pointures disponibles</h5>
                                <div class="sizes-container d-flex flex-wrap gap-2" id="details_pointures">
                                    <!-- Les pointures seront insérées ici par JavaScript -->
                                </div>
                            </div>
                            <div class="action-buttons d-flex gap-3">
                                <button class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-center gap-2" onclick="addToCart(getCurrentProductId())">
                                    <svg class="icon" style="width: 1.1em; height: 1.1em; fill: currentColor;"><use xlink:href="#shopping-cart"></use></svg>
                                    <span>Ajouter au panier</span>
                                </button>
                                <button class="btn btn-outline-danger btn-favorite d-flex align-items-center justify-content-center gap-2" onclick="toggleFavorite(getCurrentProductId())">
                                    <svg class="icon" style="width: 1.1em; height: 1.1em; fill: currentColor;"><use xlink:href="#heart-outline"></use></svg>
                                    <span>Ajouter au favoris</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Initialiser tous les dropdowns Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });

    const modalElement = document.getElementById('productDetailsModal');
    // Initialiser l'instance de la modale une seule fois au chargement de la page
    productDetailsModalInstance = new bootstrap.Modal(modalElement, {
        keyboard: true,
        focus: true
    });

    // Ajouter les écouteurs d'événements globaux pour la modale
    modalElement.addEventListener('mouseleave', () => {
        if (showModalTimeout) {
            clearTimeout(showModalTimeout);
            showModalTimeout = null;
        }
        if (hideModalTimeout) {
            clearTimeout(hideModalTimeout);
        }
        if (productDetailsModalInstance) {
            productDetailsModalInstance.hide();
        }
    });

    modalElement.addEventListener('mouseenter', () => {
        if (hideModalTimeout) {
            clearTimeout(hideModalTimeout);
            hideModalTimeout = null;
        }
    });
});
</script>
</body>
</html> 