<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$active_page = 'orders';

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

// Récupération des catégories pour le filtre
$categories = $pdo->query("SELECT DISTINCT catégorie FROM produit ORDER BY catégorie ASC")->fetchAll(PDO::FETCH_COLUMN);

// Récupération des produits commandés pour le filtre
$ordered_products = $pdo->query("
    SELECT DISTINCT p.nom
    FROM produit p
    JOIN commande_produit cp ON p.id = cp.id_produit
    ORDER BY p.nom ASC
")->fetchAll(PDO::FETCH_COLUMN);

// Récupération des commandes avec toutes les informations pour les filtres
$stmt = $pdo->query("
    SELECT
        c.id,
        c.date_commande,
        c.total,
        c.statut,
        CONCAT(u.prenom, ' ', u.nom) AS user_name,
        u.image AS user_image,
        -- Concaténer tous les noms de produits de la commande
        (SELECT GROUP_CONCAT(p.nom SEPARATOR '|||')
         FROM commande_produit cp JOIN produit p ON cp.id_produit = p.id
         WHERE cp.id_commande = c.id) AS product_names,
        -- Concaténer toutes les catégories uniques de la commande
        (SELECT GROUP_CONCAT(DISTINCT p.catégorie SEPARATOR '|||')
         FROM commande_produit cp JOIN produit p ON cp.id_produit = p.id
         WHERE cp.id_commande = c.id) AS product_categories,
        -- Vérifier s'il y a une réduction (coupon)
        ( (SELECT SUM(cp.prix_unitaire * cp.quantite) FROM commande_produit cp WHERE cp.id_commande = c.id) + 7.00 - c.total > 0.01 ) AS has_discount,
        c.date_livraison
    FROM commande c
    JOIN user u ON c.id_user = u.id
    ORDER BY c.date_commande DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commandes - Stylish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .table th, .table td { vertical-align: middle; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .user-placeholder { width: 40px; height: 40px; border-radius: 50%; background-color: #e9ecef; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #495057; }
        .status-badge { font-size: 0.85em; padding: 0.5em 0.8em; border-radius: 0.25rem; }
        .status-badge-en-attente { background-color: #ffc107 !important; color: #212529 !important; }
        .status-badge-confirmé { background-color: #0dcaf0 !important; color: #212529 !important; }
        .status-badge-en-préparation { background-color: #0d6efd !important; color: #fff !important; }
        .status-badge-expédié { background-color: #6c757d !important; color: #fff !important; }
        .status-badge-livré { background-color: #198754 !important; color: #fff !important; }
        .status-badge-select { border: none; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des Commandes <span id="order-count" class="badge bg-secondary ms-2"></span></h1>
                </div>

                <!-- Filtres -->
                <div class="card shadow-sm mb-4 filter-card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="userNameSearch" class="form-label">Nom du client</label>
                                <input type="text" class="form-control" id="userNameSearch" placeholder="Rechercher par nom de client...">
                            </div>
                            <div class="col-md-4">
                                <label for="productNameFilter" class="form-label">Produit commandé</label>
                                <select class="form-select" id="productNameFilter">
                                    <option value="">Tous les produits</option>
                                    <?php foreach ($ordered_products as $product_name): ?>
                                        <option value="<?php echo htmlspecialchars($product_name); ?>"><?php echo htmlspecialchars($product_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="dateFilter" class="form-label">Date de commande</label>
                                <input type="date" class="form-control" id="dateFilter">
                            </div>
                            <div class="col-md-4">
                                <label for="deliveryDateFilter" class="form-label">Date de livraison</label>
                                <input type="date" class="form-control" id="deliveryDateFilter">
                            </div>
                            <div class="col-md-4">
                                <label for="statusFilter" class="form-label">Statut</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="">Tous les statuts</option>
                                    <option value="en attente">En attente</option>
                                    <option value="confirmé">Confirmé</option>
                                    <option value="en préparation">En préparation</option>
                                    <option value="expédié">Expédié</option>
                                    <option value="livré">Livré</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="categoryFilter" class="form-label">Catégorie</label>
                                <select class="form-select" id="categoryFilter">
                                    <option value="">Toutes les catégories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo ucfirst(htmlspecialchars($category)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="discountFilter" class="form-label">Coupon</label>
                                <select class="form-select" id="discountFilter">
                                    <option value="">Tous(coupon)</option>
                                    <option value="yes">Avec Coupon</option>
                                    <option value="no">Sans Coupon</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Date Commande</th>
                                        <th class="text-end">Total à payer</th>
                                        <th class="text-center">Statut</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="ordersTableBody">
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Aucune commande pour le moment.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr class="order-row"
                                                data-user-name="<?php echo htmlspecialchars($order['user_name']); ?>"
                                                data-date="<?php echo date('Y-m-d', strtotime($order['date_commande'])); ?>"
                                                data-delivery-date="<?php echo $order['date_livraison'] ? date('Y-m-d', strtotime($order['date_livraison'])) : ''; ?>"
                                                data-status="<?php echo htmlspecialchars($order['statut']); ?>"
                                                data-products="<?php echo htmlspecialchars($order['product_names']); ?>"
                                                data-categories="<?php echo htmlspecialchars($order['product_categories']); ?>"
                                                data-discount="<?php echo $order['has_discount'] ? 'yes' : 'no'; ?>">
                                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($order['user_image']): ?>
                                                            <img src="<?php echo htmlspecialchars($order['user_image']); ?>" alt="Client" class="user-avatar me-2">
                                                        <?php else: ?>
                                                            <div class="user-placeholder me-2"><i class="fas fa-user"></i></div>
                                                        <?php endif; ?>
                                                        <span><?php echo htmlspecialchars($order['user_name']); ?></span>
                                                    </div>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?></td>
                                                <td class="text-end"><strong><?php echo number_format($order['total'], 2, ',', ' '); ?> DT</strong></td>
                                                <td class="text-center">
                                                    <select class="form-select form-select-sm statut-select status-badge-select status-badge-<?= str_replace(' ', '-', $order['statut']); ?>" data-id="<?php echo $order['id']; ?>">
                                                        <option value="en attente" class="bg-warning text-dark" <?php if($order['statut']=='en attente') echo 'selected'; ?>>En attente</option>
                                                        <option value="confirmé" class="bg-info text-dark" <?php if($order['statut']=='confirmé') echo 'selected'; ?>>Confirmé</option>
                                                        <option value="en préparation" class="bg-primary text-white" <?php if($order['statut']=='en préparation') echo 'selected'; ?>>En préparation</option>
                                                        <option value="expédié" class="bg-secondary text-white" <?php if($order['statut']=='expédié') echo 'selected'; ?>>Expédié</option>
                                                        <option value="livré" class="bg-success text-white" <?php if($order['statut']=='livré') echo 'selected'; ?>>Livré</option>
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="showDetails(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-eye me-1"></i> 
                                                    </button>
                                                    <?php if ($order['statut'] === 'en préparation'): ?>
                                                        <button class="btn btn-sm btn-warning ms-1" onclick="expedierCommande(<?php echo $order['id']; ?>, this)">
                                                            <i class="fas fa-truck"></i> 
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-danger ms-1" onclick="deleteCommande(<?php echo $order['id']; ?>, this)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php if ($order['statut'] == 'expédié'): ?>
                                                        <a href="livrer_commande.php?id=<?php echo $order['id']; ?>" title="Marquer comme livré">
                                                            <i class="fa fa-truck" style="color:green;font-size:1.3em;cursor:pointer;"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Détails Commande -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalTitle">Détails de la commande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                    <!-- Contenu chargé via AJAX -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-success" id="sendEmailBtn" disabled>
                        <i class="fas fa-envelope me-1"></i> Envoyer par e-mail (PDF)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour la date de livraison -->
    <div class="modal fade" id="deliveryDateModal" tabindex="-1" aria-labelledby="deliveryDateModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deliveryDateModalLabel">Date de livraison estimée</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="deliveryDateForm">
                        <input type="hidden" id="orderIdForEmail" value="">
                        <div class="mb-3">
                            <label for="deliveryDateInput" class="form-label">Veuillez sélectionner une date et une heure de livraison :</label>
                            <input type="datetime-local" class="form-control" id="deliveryDateInput" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="confirmSendEmailBtn">Confirmer et envoyer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filters = {
                userName: document.getElementById('userNameSearch'),
                productName: document.getElementById('productNameFilter'),
                date: document.getElementById('dateFilter'),
                deliveryDate: document.getElementById('deliveryDateFilter'),
                status: document.getElementById('statusFilter'),
                category: document.getElementById('categoryFilter'),
                discount: document.getElementById('discountFilter')
            };

            function filterOrders() {
                const userNameValue = filters.userName.value.toLowerCase();
                const productNameValue = filters.productName.value.toLowerCase();
                const dateValue = filters.date.value;
                const deliveryDateValue = filters.deliveryDate.value;
                const statusValue = filters.status.value;
                const categoryValue = filters.category.value;
                const discountValue = filters.discount.value;
                
                let visibleCount = 0;
                const rows = document.querySelectorAll('#ordersTableBody .order-row');

                rows.forEach(row => {
                    const rowUserName = row.dataset.userName.toLowerCase();
                    const rowDate = row.dataset.date;
                    const rowDeliveryDate = row.dataset.deliveryDate || '';
                    const rowStatus = row.dataset.status;
                    const rowDiscount = row.dataset.discount;
                    const rowProducts = row.dataset.products.toLowerCase();
                    const rowCategories = row.dataset.categories ? row.dataset.categories.toLowerCase() : '';

                    const userNameMatch = !userNameValue || rowUserName.includes(userNameValue);
                    const productNameMatch = !productNameValue || rowProducts.split('|||').includes(productNameValue.trim());
                    const dateMatch = !dateValue || rowDate === dateValue;
                    const deliveryDateMatch = !deliveryDateValue || rowDeliveryDate === deliveryDateValue;
                    const statusMatch = !statusValue || rowStatus === statusValue;
                    const categoryMatch = !categoryValue || (rowCategories && rowCategories.split('|||').includes(categoryValue));
                    const discountMatch = !discountValue || rowDiscount === discountValue;

                    if (userNameMatch && productNameMatch && dateMatch && deliveryDateMatch && statusMatch && categoryMatch && discountMatch) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                document.getElementById('order-count').textContent = visibleCount;
            }

            // Initial count
            filterOrders();

            // Attach event listeners
            Object.values(filters).forEach(filter => {
                filter.addEventListener('input', filterOrders);
                filter.addEventListener('change', filterOrders);
            });
        });

        let currentOrderId = null;
        const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
        const deliveryDateModal = new bootstrap.Modal(document.getElementById('deliveryDateModal'));

        function showDetails(orderId) {
            currentOrderId = orderId;
            const modalBody = document.getElementById('detailsModalBody');
            const modalTitle = document.getElementById('detailsModalTitle');
            const sendEmailBtn = document.getElementById('sendEmailBtn');

            modalTitle.textContent = 'Détails de la commande #' + orderId;
            modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div></div>';
            sendEmailBtn.disabled = true;

            fetch('get_order_details.php?id=' + orderId)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                    sendEmailBtn.disabled = false;
                })
                .catch(error => {
                    modalBody.innerHTML = '<div class="alert alert-danger">Erreur de chargement des détails.</div>';
                    console.error('Error:', error);
                });
            
            detailsModal.show();
        }

        document.getElementById('sendEmailBtn').addEventListener('click', function() {
            if (currentOrderId) {
                document.getElementById('orderIdForEmail').value = currentOrderId;
                detailsModal.hide();
                deliveryDateModal.show();
            }
        });

        document.getElementById('confirmSendEmailBtn').addEventListener('click', function() {
            const orderId = document.getElementById('orderIdForEmail').value;
            const deliveryDate = document.getElementById('deliveryDateInput').value;

            if (!deliveryDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Veuillez sélectionner une date et une heure de livraison.',
                });
                return;
            }

            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...';

            const formData = new FormData();
            formData.append('id', orderId);
            formData.append('date_livraison', deliveryDate);

            fetch('send_order_details.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    deliveryDateModal.hide();
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès!',
                        text: 'L\'e-mail avec la facture a été envoyé et le statut de la commande mis à jour.',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload(); // Recharger pour voir le statut mis à jour
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: data.message || 'Une erreur est survenue.',
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Une erreur de communication est survenue.',
                });
                console.error('Error:', error);
            })
            .finally(() => {
                const btn = document.getElementById('confirmSendEmailBtn');
                btn.disabled = false;
                btn.innerHTML = 'Confirmer et envoyer';
            });
        });

        // Gestionnaire pour le formulaire d'expédition
        document.addEventListener('submit', function(e) {
            // --- Gestion modification date livraison ---
            if (e.target && e.target.id === 'updateLivraisonForm') {
                e.preventDefault();
                const form = e.target;
                const msgDiv = form.parentElement.querySelector('#updateLivraisonMsg');
                msgDiv.innerHTML = '';
                const btn = form.querySelector('button[type="submit"]');
                const originalBtn = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mise à jour...';

                const formData = new FormData();
                formData.append('id', currentOrderId);
                formData.append('date_livraison', form.date_livraison.value);

                fetch('update_livraison.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        msgDiv.innerHTML = '<div class="alert alert-success py-2">' + data.message + '</div>';
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        msgDiv.innerHTML = '<div class="alert alert-danger py-2">' + (data.message || 'Erreur inconnue') + '</div>';
                    }
                })
                .catch(() => {
                    msgDiv.innerHTML = '<div class="alert alert-danger py-2">Erreur de communication avec le serveur.</div>';
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalBtn;
                });
                return;
            }
        });

        function expedierCommande(orderId, btn) {
            Swal.fire({
                title: 'Êtes-vous sûr de vouloir expédier cette commande ?',
                text: 'Cette action est irréversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, expédier',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Expédition...';

                    fetch('expedier_commande.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + encodeURIComponent(orderId)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Commande expédiée',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: data.message || 'Une erreur est survenue.'
                            });
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-truck"></i> Expédier';
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Erreur de communication avec le serveur.'
                        });
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-truck"></i> Expédier';
                    });
                }
            });
        }

        function deleteCommande(orderId, btn) {
            Swal.fire({
                title: 'Supprimer la commande ?',
                text: 'Cette action est irréversible. Voulez-vous vraiment supprimer cette commande ?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Suppression...';

                    fetch('delete_commande.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id=' + encodeURIComponent(orderId)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Commande supprimée',
                                text: data.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erreur',
                                text: data.message || 'Une erreur est survenue.'
                            });
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-trash"></i> Supprimer';
                        }
                    })
                    .catch(() => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: 'Erreur de communication avec le serveur.'
                        });
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-trash"></i> Supprimer';
                    });
                }
            });
        }

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('statut-select')) {
                const orderId = e.target.getAttribute('data-id');
                const newStatus = e.target.value;
                // Mettre à jour la classe couleur du select
                e.target.className = 'form-select form-select-sm statut-select status-badge-select status-badge-' + newStatus.replace(/ /g, '-');
                fetch('update_commande_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(orderId) + '&statut=' + encodeURIComponent(newStatus)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Statut mis à jour',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: data.message || 'Une erreur est survenue.'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Erreur de communication avec le serveur.'
                    });
                });
            }
        });
    </script>
</body>
</html> 