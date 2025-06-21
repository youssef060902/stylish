<?php
session_start();

// Configuration du fuseau horaire de Tunis
date_default_timezone_set('Africa/Tunis');

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

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

// Récupération des promotions
try {
    $stmt = $pdo->query("SELECT p.*, COUNT(pr.id) as nombre_produits 
                         FROM promotion p 
                         LEFT JOIN produit pr ON p.id = pr.id_promotion 
                         GROUP BY p.id 
                         ORDER BY p.date_debut DESC");
    $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de récupération des promotions : " . $e->getMessage());
}

$active_page = 'promotions';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Promotions - Stylish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .promotion-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
            position: relative;
        }
        .promotion-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .promotion-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #ff6b6b, #ff8e8e);
        }
        .discount-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: linear-gradient(135deg, #ff6b6b, #ff8e8e);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.2em;
            box-shadow: 0 4px 15px rgba(255,107,107,0.3);
            z-index: 1;
        }
        .date-badge {
            background: #f8f9fa;
            color: #495057;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.95em;
            margin-bottom: 10px;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .date-badge:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        .date-badge i {
            color: #ff6b6b;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .status-active { 
            background: linear-gradient(135deg, #28a745, #34c759);
            color: white;
        }
        .status-upcoming { 
            background: linear-gradient(135deg, #ffc107, #ffd54f);
            color: #000;
        }
        .status-ended { 
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            color: white;
        }
        .card-body {
            padding: 25px;
        }
        .card-title {
            font-size: 1.4em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-right: 80px;
        }
        .card-text {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .products-count {
            background: #e9ecef;
            color: #495057;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .products-count i {
            color: #3498db;
        }
        .card-footer {
            background: #f8f9fa;
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 15px 25px;
        }
        .btn-group {
            gap: 10px;
        }
        .btn-group .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-group .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
        }
        .btn-group .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border: none;
        }
        .btn-group .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .search-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 5px;
        }
        .search-container .input-group {
            border: none;
        }
        .search-container .input-group-text {
            border: none;
            background: transparent;
        }
        .search-container .form-control {
            border: none;
            padding: 10px 15px;
        }
        .search-container .form-control:focus {
            box-shadow: none;
        }
        .add-promotion-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .add-promotion-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.3);
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
                    <div class="d-flex align-items-center">
                        <h2 class="mb-0">Liste des Promotions</h2>
                        <div class="ms-3 badge bg-primary" style="font-size: 1.1em;">
                            <i class="fas fa-tag me-1"></i>
                            <span id="promotion-count"><?php echo count($promotions); ?></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="input-group search-container me-3" style="width: 300px;">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" id="searchPromotion" class="form-control" placeholder="Rechercher une promotion...">
                        </div>
                        <button class="btn btn-primary add-promotion-btn" data-bs-toggle="modal" data-bs-target="#addPromotionModal">
                            <i class="fas fa-plus me-2"></i>Nouvelle promotion
                        </button>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filtres</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Statut</label>
                                <select class="form-select filter-select" name="status">
                                    <option value="">Tous les statuts</option>
                                    <option value="active">Actives</option>
                                    <option value="upcoming">À venir</option>
                                    <option value="ended">Terminées</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Remise minimale</label>
                                <input type="number" class="form-control filter-input" name="discount_min" min="0" max="100" placeholder="Min %">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Remise maximale</label>
                                <input type="number" class="form-control filter-input" name="discount_max" min="0" max="100" placeholder="Max %">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date de début</label>
                                <input type="datetime-local" class="form-control filter-input" name="date_debut">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date de fin</label>
                                <input type="datetime-local" class="form-control filter-input" name="date_fin">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Nombre de produits minimum</label>
                                <input type="number" class="form-control filter-input" name="products_min" min="0" placeholder="Min produits">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grille des promotions -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php if (empty($promotions)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-percent fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Aucune promotion trouvée</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($promotions as $promotion): 
                        $tz_tunis = new DateTimeZone('Africa/Tunis');
                        $now = new DateTime('now', $tz_tunis); // Heure actuelle dans le fuseau horaire de Tunis

                        // Convertir les dates de la base de données en DateTime avec le fuseau horaire de Tunis
                        $debut = new DateTime($promotion['date_debut'], $tz_tunis);
                        $fin = new DateTime($promotion['date_fin'], $tz_tunis);
                        
                        if ($now < $debut) {
                            $status = 'upcoming';
                            $statusText = 'À venir';
                        } elseif ($now >= $debut && $now <= $fin) {
                            $status = 'active';
                            $statusText = 'Active';
                        } else {
                            $status = 'ended';
                            $statusText = 'Terminée';
                        }
                    ?>
                    <div class="col">
                        <div class="promotion-card" 
                             data-date-debut="<?php echo $promotion['date_debut']; ?>"
                             data-date-fin="<?php echo $promotion['date_fin']; ?>"
                             data-status="<?php echo $status; ?>"
                             data-discount="<?php echo $promotion['discount']; ?>"
                             data-products="<?php echo $promotion['nombre_produits']; ?>">
                            <div class="discount-badge">-<?php echo $promotion['discount']; ?>%</div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($promotion['nom']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($promotion['description']); ?></p>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <div class="date-badge">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Début: <?php echo date('d/m/Y H:i', strtotime($promotion['date_debut'])); ?>
                                    </div>
                                    <div class="date-badge">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        Fin: <?php echo date('d/m/Y H:i', strtotime($promotion['date_fin'])); ?>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="status-badge <?php echo 'status-' . $status; ?>" data-status="<?php echo $status; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                    <span class="products-count" style="cursor: pointer;" onclick="showPromotionProducts(<?php echo $promotion['id']; ?>)">
                                        <i class="fas fa-box me-1"></i>
                                        <?php echo $promotion['nombre_produits']; ?> produits
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100">
                                    <button type="button" class="btn btn-primary" onclick="editPromotion(<?php echo $promotion['id']; ?>)">
                                        <i class="fas fa-edit me-1"></i> Modifier
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="deletePromotion(<?php echo $promotion['id']; ?>)">
                                        <i class="fas fa-trash me-1"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout Promotion -->
    <div class="modal fade" id="addPromotionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une promotion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPromotionForm">
                        <div class="mb-3">
                            <label class="form-label">Nom de la promotion</label>
                            <input type="text" class="form-control" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date de début</label>
                                <input type="datetime-local" class="form-control" name="date_debut" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date de fin</label>
                                <input type="datetime-local" class="form-control" name="date_fin" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Réduction (%)</label>
                            <input type="number" class="form-control" name="discount" min="1" max="99" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="savePromotion()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Édition Promotion -->
    <div class="modal fade" id="editPromotionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la promotion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editPromotionForm">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">Nom de la promotion</label>
                            <input type="text" class="form-control" name="nom" id="edit_nom" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date de début</label>
                                <input type="datetime-local" class="form-control" name="date_debut" id="edit_date_debut" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date de fin</label>
                                <input type="datetime-local" class="form-control" name="date_fin" id="edit_date_fin" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Réduction (%)</label>
                            <input type="number" class="form-control" name="discount" id="edit_discount" min="1" max="99" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="updatePromotion()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal des produits de la promotion -->
    <div class="modal fade" id="promotionProductsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Produits en promotion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Image</th>
                                    <th>Nom</th>
                                    <th>Marque</th>
                                    <th>Prix initial</th>
                                    <th>Prix réduit</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody id="promotionProductsList">
                                <!-- Les produits seront chargés ici dynamiquement -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fonction pour filtrer les promotions
        function filterPromotions() {
            const searchValue = document.getElementById('searchPromotion').value.toLowerCase();
            const cards = document.querySelectorAll('.promotion-card');
            let visibleCount = 0;

            // Récupérer les valeurs des filtres
            const statusFilter = document.querySelector('select[name="status"]').value;
            const discountMin = document.querySelector('input[name="discount_min"]').value ? parseFloat(document.querySelector('input[name="discount_min"]').value) : 0;
            const discountMax = document.querySelector('input[name="discount_max"]').value ? parseFloat(document.querySelector('input[name="discount_max"]').value) : 100;
            const dateDebut = document.querySelector('input[name="date_debut"]').value;
            const dateFin = document.querySelector('input[name="date_fin"]').value;
            const productsMin = document.querySelector('input[name="products_min"]').value ? parseInt(document.querySelector('input[name="products_min"]').value) : 0;

            cards.forEach(card => {
                const nom = card.querySelector('.card-title').textContent.toLowerCase();
                const discount = parseFloat(card.querySelector('.discount-badge').textContent.replace('%', '').replace('-', ''));
                const status = card.querySelector('.status-badge').getAttribute('data-status');
                const productsCount = parseInt(card.querySelector('.products-count').textContent);
                const dateDebutPromo = card.getAttribute('data-date-debut');
                const dateFinPromo = card.getAttribute('data-date-fin');

                // Vérifier si la promotion correspond aux critères de recherche
                const matchesSearch = nom.includes(searchValue);
                
                // Vérifier le statut
                const matchesStatus = !statusFilter || status === statusFilter;
                
                // Vérifier la remise
                const matchesDiscount = discount >= discountMin && discount <= discountMax;
                
                // Vérifier le nombre de produits
                const matchesProducts = productsCount >= productsMin;
                
                // Vérifier les dates
                let matchesDates = true;
                if (dateDebut) {
                    matchesDates = matchesDates && new Date(dateDebutPromo) >= new Date(dateDebut);
                }
                if (dateFin) {
                    matchesDates = matchesDates && new Date(dateFinPromo) <= new Date(dateFin);
                }

                // Appliquer tous les filtres
                if (matchesSearch && matchesStatus && matchesDiscount && matchesProducts && matchesDates) {
                    card.closest('.col').style.display = '';
                    visibleCount++;
                } else {
                    card.closest('.col').style.display = 'none';
                }
            });

            // Mettre à jour le compteur
            document.getElementById('promotion-count').textContent = visibleCount;
        }

        // Ajouter les écouteurs d'événements pour tous les filtres
        document.querySelectorAll('.filter-select, .filter-input').forEach(element => {
            element.addEventListener('change', filterPromotions);
            element.addEventListener('input', filterPromotions);
        });

        // Modifier l'écouteur d'événement de recherche existant
        document.getElementById('searchPromotion').addEventListener('keyup', filterPromotions);

        // Appeler filterPromotions au chargement de la page
        document.addEventListener('DOMContentLoaded', filterPromotions);

        // Fonction pour déterminer le statut d'une promotion
        function getPromotionStatus(dateDebut, dateFin) {
            const now = new Date();
            const debut = new Date(dateDebut);
            const fin = new Date(dateFin);

            if (now < debut) {
                return 'upcoming';
            } else if (now >= debut && now <= fin) {
                return 'active';
            } else {
                return 'ended';
            }
        }

        // Fonction pour formater la date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('fr-FR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Fonction pour éditer une promotion
        function editPromotion(id) {
            fetch(`get_promotion.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const promotion = data.data;
                        document.getElementById('edit_id').value = promotion.id;
                        document.getElementById('edit_nom').value = promotion.nom;
                        document.getElementById('edit_description').value = promotion.description;
                        
                        // Formatage des dates pour l'input datetime-local
                        const formatLocalDateTime = (dateString) => {
                            const date = new Date(dateString);
                            const year = date.getFullYear();
                            const month = (date.getMonth() + 1).toString().padStart(2, '0');
                            const day = date.getDate().toString().padStart(2, '0');
                            const hours = date.getHours().toString().padStart(2, '0');
                            const minutes = date.getMinutes().toString().padStart(2, '0');
                            return `${year}-${month}-${day}T${hours}:${minutes}`;
                        };

                        document.getElementById('edit_date_debut').value = formatLocalDateTime(promotion.date_debut);
                        document.getElementById('edit_date_fin').value = formatLocalDateTime(promotion.date_fin);
                        
                        document.getElementById('edit_discount').value = promotion.discount;
                        
                        // Afficher la modal
                        const modal = new bootstrap.Modal(document.getElementById('editPromotionModal'));
                        modal.show();
                    } else {
                        Swal.fire('Erreur', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Erreur', 'Une erreur est survenue lors du chargement des données', 'error');
                });
        }

        // Fonction pour supprimer une promotion
        function deletePromotion(id) {
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('delete_promotion.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'id=' + id
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Supprimé !', 'La promotion a été supprimée avec succès.', 'success')
                            .then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Erreur !', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Erreur !', 'Une erreur est survenue lors de la suppression.', 'error');
                    });
                }
            });
        }

        // Fonction pour sauvegarder une nouvelle promotion
        function savePromotion() {
            const form = document.getElementById('addPromotionForm');
            const formData = new FormData(form);

            // Validation des dates
            const dateDebut = new Date(formData.get('date_debut'));
            const dateFin = new Date(formData.get('date_fin'));

            if (dateFin <= dateDebut) {
                Swal.fire('Erreur', 'La date de fin doit être postérieure à la date de début', 'error');
                return;
            }

            fetch('add_promotion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Succès!', 'Promotion ajoutée avec succès', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
            });
        }

        // Fonction pour mettre à jour une promotion
        function updatePromotion() {
            const form = document.getElementById('editPromotionForm');
            const formData = new FormData(form);

            // Validation des dates
            const dateDebut = new Date(formData.get('date_debut'));
            const dateFin = new Date(formData.get('date_fin'));

            if (dateFin <= dateDebut) {
                Swal.fire('Erreur', 'La date de fin doit être postérieure à la date de début', 'error');
                return;
            }

            fetch('update_promotion.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Succès!', 'Promotion mise à jour avec succès', 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
            });
        }

        // Suppression d'une promotion
        document.querySelectorAll('.btn-delete-promotion').forEach(btn => {
            btn.addEventListener('click', function() {
                const promotionId = this.getAttribute('data-promotion-id');
                
                deletePromotion(promotionId);
            });
        });

        function showPromotionProducts(promotionId) {
            // Afficher la modal
            const modal = new bootstrap.Modal(document.getElementById('promotionProductsModal'));
            modal.show();

            // Charger les produits de la promotion
            fetch(`get_promotion_products.php?id=${promotionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('promotionProductsList');
                        tbody.innerHTML = '';

                        data.products.forEach(product => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>
                                    <img src="${product.image || 'path/to/default-image.jpg'}" 
                                         alt="Image produit" 
                                         class="product-image" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td>${product.nom}</td>
                                <td>${product.marque}</td>
                                <td>${product.prix_initial} €</td>
                                <td>${product.prix_reduit} €</td>
                                <td>
                                    <span class="badge ${product.statut === 'en stock' ? 'bg-success' : 'bg-warning'}">
                                        ${product.statut}
                                    </span>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });
                    } else {
                        Swal.fire('Erreur', data.message, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Erreur', 'Une erreur est survenue lors du chargement des produits', 'error');
                });
        }
    </script>
</body>
</html> 