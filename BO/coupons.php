<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$active_page = 'coupons';
require_once '../config/database.php';

// Récupérer tous les coupons
try {
    $stmt = $pdo->query("SELECT id, code, discount, statut FROM coupon ORDER BY id DESC");
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les réductions distinctes pour le filtre
    $stmt_discounts = $pdo->query("SELECT DISTINCT discount FROM coupon ORDER BY discount ASC");
    $distinct_discounts = $stmt_discounts->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Erreur de récupération des données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Coupons - Stylish</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .form-switch .form-check-input { width: 3.5em; height: 1.75em; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestion des Coupons <span id="coupon-count" class="badge bg-secondary ms-2"></span></h1>
                    <button class="btn btn-primary" id="addCouponBtn"><i class="fas fa-plus me-2"></i> Ajouter un coupon</button>
                </div>

                <!-- Filtres -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="statusFilter" class="form-label">Filtrer par statut</label>
                                <select id="statusFilter" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="active">Actif</option>
                                    <option value="inactive">Inactif</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="discountFilter" class="form-label">Filtrer par réduction</label>
                                <select id="discountFilter" class="form-select">
                                    <option value="">Toutes</option>
                                    <?php foreach ($distinct_discounts as $discount): ?>
                                    <option value="<?php echo $discount; ?>"><?php echo $discount; ?>%</option>
                                    <?php endforeach; ?>
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
                                        <th>Code</th>
                                        <th class="text-center">Réduction (%)</th>
                                        <th class="text-center">Statut</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="couponsTableBody">
                                    <?php foreach ($coupons as $coupon): ?>
                                    <tr id="coupon-row-<?php echo $coupon['id']; ?>" data-status="<?php echo $coupon['statut']; ?>" data-discount="<?php echo $coupon['discount']; ?>">
                                        <td><?php echo htmlspecialchars($coupon['code']); ?></td>
                                        <td class="text-center"><?php echo $coupon['discount']; ?>%</td>
                                        <td class="text-center">
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch" data-id="<?php echo $coupon['id']; ?>" <?php echo $coupon['statut'] === 'active' ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-outline-secondary edit-btn" data-id="<?php echo $coupon['id']; ?>"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?php echo $coupon['id']; ?>"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Ajout/Modification Coupon -->
    <div class="modal fade" id="couponModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="couponModalTitle">Ajouter un Coupon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="couponForm">
                    <div class="modal-body">
                        <div class="text-center d-none" id="couponModalLoader">
                            <div class="spinner-border" role="status"><span class="visually-hidden">Chargement...</span></div>
                        </div>
                        <div id="couponModalFormContent">
                            <input type="hidden" id="couponId" name="id">
                            <div class="mb-3">
                                <label for="couponCode" class="form-label">Code du coupon</label>
                                <input type="text" class="form-control" id="couponCode" name="code" required>
                            </div>
                            <div class="mb-3">
                                <label for="couponDiscount" class="form-label">Réduction (en %)</label>
                                <input type="number" class="form-control" id="couponDiscount" name="discount" min="1" max="100" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const couponModal = new bootstrap.Modal(document.getElementById('couponModal'));
            const couponForm = document.getElementById('couponForm');
            const modalTitle = document.getElementById('couponModalTitle');
            const couponIdInput = document.getElementById('couponId');
            const statusFilter = document.getElementById('statusFilter');
            const discountFilter = document.getElementById('discountFilter');
            const loader = document.getElementById('couponModalLoader');
            const formContent = document.getElementById('couponModalFormContent');

            function updateCouponCount() {
                let visibleCount = 0;
                document.querySelectorAll('#couponsTableBody tr').forEach(row => {
                    if (row.style.display !== 'none') {
                        visibleCount++;
                    }
                });
                document.getElementById('coupon-count').textContent = `${visibleCount}`;
            }

            function filterCoupons() {
                const selectedStatus = statusFilter.value;
                const selectedDiscount = discountFilter.value;

                document.querySelectorAll('#couponsTableBody tr').forEach(row => {
                    const rowStatus = row.dataset.status;
                    const rowDiscount = row.dataset.discount;

                    const statusMatch = (selectedStatus === '' || rowStatus === selectedStatus);
                    const discountMatch = (selectedDiscount === '' || rowDiscount === selectedDiscount);

                    if (statusMatch && discountMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                updateCouponCount();
            }

            statusFilter.addEventListener('change', filterCoupons);
            discountFilter.addEventListener('change', filterCoupons);
            updateCouponCount(); // Initial count

            // Ouvrir le modal pour ajouter un coupon
            document.getElementById('addCouponBtn').addEventListener('click', function () {
                couponForm.reset();
                couponIdInput.value = '';
                modalTitle.textContent = 'Ajouter un Coupon';
                loader.classList.add('d-none');
                formContent.classList.remove('d-none');
                couponModal.show();
            });

            // Soumission du formulaire (Ajout/Modification)
            couponForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const formData = new FormData(this);
                const url = 'add_edit_coupon.php';

                fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        couponModal.hide();
                        Swal.fire('Succès', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erreur', data.message, 'error');
                    }
                });
            });

            // Ouvrir le modal pour modifier un coupon
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.dataset.id;
                    couponForm.reset();
                    modalTitle.textContent = 'Modifier le Coupon';
                    loader.classList.remove('d-none');
                    formContent.classList.add('d-none');
                    couponModal.show();

                    fetch(`get_coupon.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data) {
                                couponIdInput.value = data.id;
                                document.getElementById('couponCode').value = data.code;
                                document.getElementById('couponDiscount').value = data.discount;
                                loader.classList.add('d-none');
                                formContent.classList.remove('d-none');
                            } else {
                                couponModal.hide();
                                Swal.fire('Erreur', 'Impossible de charger les données.', 'error');
                            }
                        })
                        .catch(() => {
                            couponModal.hide();
                            Swal.fire('Erreur', 'Une erreur de communication est survenue.', 'error');
                        });
                });
            });

            // Supprimer un coupon
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.dataset.id;
                    Swal.fire({
                        title: 'Êtes-vous sûr ?',
                        text: "Cette action est irréversible !",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Oui, supprimer !'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            fetch('delete_coupon.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `id=${id}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById(`coupon-row-${id}`).remove();
                                    Swal.fire('Supprimé !', data.message, 'success');
                                } else {
                                    Swal.fire('Erreur', data.message, 'error');
                                }
                            });
                        }
                    });
                });
            });

            // Mettre à jour le statut
            document.querySelectorAll('.status-toggle').forEach(toggle => {
                toggle.addEventListener('change', function () {
                    const id = this.dataset.id;
                    const status = this.checked ? 'active' : 'inactive';
                    fetch('update_coupon_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${id}&status=${status}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            Swal.fire('Erreur', data.message, 'error');
                            // Revert the switch
                            this.checked = !this.checked;
                        }
                    });
                });
            });
        });
    </script>
</body>
</html> 