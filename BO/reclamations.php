<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$active_page = 'claims';

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

// Récupération des utilisateurs ayant des réclamations pour le filtre
$users_with_claims = $pdo->query("
    SELECT DISTINCT u.id, CONCAT(u.prenom, ' ', u.nom) as user_name
    FROM user u
    JOIN reclamation r ON u.id = r.id_user
    ORDER BY user_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des réclamations avec les noms d'utilisateur et de produit
$stmt = $pdo->query("
    SELECT
        r.id, r.description, r.date_creation, r.statut, r.type, r.id_user,
        CONCAT(u.prenom, ' ', u.nom) AS user_name,
        u.image AS user_image,
        u.email AS user_email,
        p.nom AS product_name
    FROM reclamation r
    JOIN user u ON r.id_user = u.id
    LEFT JOIN produit p ON r.id_produit = p.id
    ORDER BY r.date_creation DESC
");
$reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réclamations - Stylish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .table th, .table td { vertical-align: middle; }
        .description-cell {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .status-select { min-width: 120px; }
        
        /* Styles pour la modale de détails */
        .reclamation-details .detail-header,
        .reclamation-details .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        .reclamation-details .detail-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .reclamation-details .detail-item i {
            font-size: 1.5rem;
        }
        .reclamation-details .detail-item div {
            display: flex;
            flex-direction: column;
        }
        .reclamation-details .description-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .reclamation-details .description-content {
            white-space: pre-wrap;
            word-break: break-word;
        }
        #detailsModal .modal-title {
            font-weight: 600;
        }
        .modal-user-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dee2e6;
        }
        .modal-user-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #495057;
        }
        a.user-email-link {
            font-size: 0.9em;
            text-decoration: none;
            color: #0d6efd;
        }
        a.user-email-link:hover {
            text-decoration: underline;
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
                <h2 class="mb-4">Gestion des Réclamations</h2>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filtres</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Statut</label>
                                <select class="form-select filter-select" id="statusFilter">
                                    <option value="">Tous</option>
                                    <option value="nouveau">Nouveau</option>
                                    <option value="en cours">En cours</option>
                                    <option value="résolu">Résolu</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type</label>
                                <select class="form-select filter-select" id="typeFilter">
                                    <option value="">Tous</option>
                                    <option value="produit">Produit</option>
                                    <option value="livraison">Livraison</option>
                                    <option value="service">Service</option>
                                    <option value="paiement">Paiement</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Utilisateur</label>
                                <select class="form-select filter-select" id="userFilter">
                                    <option value="">Tous les utilisateurs</option>
                                    <?php foreach($users_with_claims as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['user_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau des réclamations -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Utilisateur</th>
                                <th>Produit</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="reclamationsTableBody">
                            <?php foreach ($reclamations as $r): ?>
                                <tr class="reclamation-row" 
                                    data-status="<?php echo $r['statut']; ?>"
                                    data-type="<?php echo $r['type']; ?>"
                                    data-date="<?php echo date('Y-m-d', strtotime($r['date_creation'])); ?>"
                                    data-user-id="<?php echo $r['id_user']; ?>">
                                    <td><?php echo $r['id']; ?></td>
                                    <td><?php echo htmlspecialchars($r['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($r['product_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($r['type']); ?></td>
                                    <td class="description-cell" title="<?php echo htmlspecialchars($r['description']); ?>">
                                        <?php echo htmlspecialchars($r['description']); ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($r['date_creation'])); ?></td>
                                    <td>
                                        <select class="form-select form-select-sm status-select" onchange="updateStatus(<?php echo $r['id']; ?>, this.value)">
                                            <option value="nouveau" <?php echo ($r['statut'] == 'nouveau') ? 'selected' : ''; ?>>Nouveau</option>
                                            <option value="en cours" <?php echo ($r['statut'] == 'en cours') ? 'selected' : ''; ?>>En cours</option>
                                            <option value="résolu" <?php echo ($r['statut'] == 'résolu') ? 'selected' : ''; ?>>Résolu</option>
                                        </select>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick='showDetails(<?php echo json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails Réclamation -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalTitle">Détails de la réclamation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsModalBody">
                    <!-- Contenu chargé par JS -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function updateStatus(id, newStatus) {
            fetch('update_reclamation_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}&statut=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Statut mis à jour',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    // Mettre à jour l'attribut data-status pour le filtre dynamique
                    const row = document.querySelector(`tr[data-id='${id}']`); // Vous devrez ajouter data-id aux <tr> pour que cela marche
                    if(row) row.dataset.status = newStatus;
                } else {
                    Swal.fire('Erreur', data.message, 'error');
                }
            });
        }

        function getStatusBadgeClass(status) {
            switch (status) {
                case 'nouveau': return 'bg-primary';
                case 'en cours': return 'bg-warning text-dark';
                case 'résolu': return 'bg-success';
                default: return 'bg-secondary';
            }
        }

        let detailsModal;
        function showDetails(reclamation) {
            if (!detailsModal) {
                detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
            }
            const modalTitle = document.getElementById('detailsModalTitle');
            const modalBody = document.getElementById('detailsModalBody');

            modalTitle.innerHTML = `Détails de la Réclamation #${reclamation.id}`;

            const statusBadgeClass = getStatusBadgeClass(reclamation.statut);
            
            let userImageHtml = reclamation.user_image 
                ? `<img src="${reclamation.user_image}" class="modal-user-image" alt="Photo de ${reclamation.user_name}">`
                : `<div class="modal-user-placeholder"><i class="fas fa-user"></i></div>`;

            modalBody.innerHTML = `
                <div class="reclamation-details">
                    <div class="detail-header">
                        <div class="detail-item">
                            ${userImageHtml}
                            <div>
                                <small class="text-muted">Utilisateur</small>
                                <strong>${reclamation.user_name}</strong>
                                <a href="mailto:${reclamation.user_email}" class="user-email-link">${reclamation.user_email}</a>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-box text-muted"></i>
                            <div>
                                <small class="text-muted">Produit</small>
                                <strong>${reclamation.product_name || 'Non applicable'}</strong>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <i class="fas fa-info-circle text-muted"></i>
                            <div>
                                <small class="text-muted">Type</small>
                                <span class="badge bg-secondary">${reclamation.type}</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar-alt text-muted"></i>
                            <div>
                                <small class="text-muted">Date</small>
                                <span>${new Date(reclamation.date_creation).toLocaleString('fr-FR')}</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-flag text-muted"></i>
                            <div>
                                <small class="text-muted">Statut</small>
                                <span class="badge ${statusBadgeClass}">${reclamation.statut}</span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="description-section">
                        <h6 class="mb-2">Description complète</h6>
                        <p class="description-content">${reclamation.description}</p>
                    </div>
                </div>
            `;
            
            detailsModal.show();
        }

        // Filtres dynamiques
        document.querySelectorAll('.filter-select').forEach(el => {
            el.addEventListener('change', () => {
                const statusFilter = document.getElementById('statusFilter').value;
                const typeFilter = document.getElementById('typeFilter').value;
                const userFilter = document.getElementById('userFilter').value;

                document.querySelectorAll('.reclamation-row').forEach(row => {
                    const rowStatus = row.dataset.status;
                    const rowType = row.dataset.type;
                    const rowUserId = row.dataset.userId;

                    const statusMatch = !statusFilter || rowStatus === statusFilter;
                    const typeMatch = !typeFilter || rowType === typeFilter;
                    const userMatch = !userFilter || rowUserId === userFilter;

                    if (statusMatch && typeMatch && userMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html> 