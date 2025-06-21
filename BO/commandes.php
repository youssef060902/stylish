<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$active_page = 'orders';

// Connexion à la base de données
// require '../config/database.php';
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

// Récupération des commandes
$stmt = $pdo->query("
    SELECT
        c.id, c.date_commande, c.total, c.statut,
        CONCAT(u.prenom, ' ', u.nom) AS user_name,
        u.image AS user_image
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
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .user-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #495057;
        }
        .status-badge {
            font-size: 0.85em;
            padding: 0.5em 0.8em;
            border-radius: 0.25rem;
        }
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
                    <h1 class="h2">Gestion des Commandes</h1>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Date</th>
                                        <th class="text-end">Total</th>
                                        <th class="text-center">Statut</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Aucune commande pour le moment.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
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
                                                    <?php
                                                        $status_classes = [
                                                            'en attente' => 'bg-warning text-dark',
                                                            'confirmé' => 'bg-info text-dark',
                                                            'en cours' => 'bg-primary',
                                                            'livré' => 'bg-success',
                                                        ];
                                                        $status_class = $status_classes[$order['statut']] ?? 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?> status-badge"><?php echo ucfirst($order['statut']); ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="showDetails(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-eye me-1"></i> Voir
                                                    </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));

        function showDetails(orderId) {
            const modalTitle = document.getElementById('detailsModalTitle');
            const modalBody = document.getElementById('detailsModalBody');
            const sendEmailBtn = document.getElementById('sendEmailBtn');

            modalTitle.textContent = `Détails de la Commande #${orderId}`;
            modalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div></div>';
            sendEmailBtn.disabled = true;
            detailsModal.show();

            fetch(`get_order_details.php?id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                    sendEmailBtn.disabled = false;
                    sendEmailBtn.onclick = () => sendEmail(orderId);
                })
                .catch(error => {
                    modalBody.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement des détails.</div>';
                    console.error('Error:', error);
                });
        }

        function sendEmail(orderId) {
            const sendEmailBtn = document.getElementById('sendEmailBtn');
            sendEmailBtn.disabled = true;
            sendEmailBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi...';

            fetch('send_order_details.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${orderId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'E-mail envoyé !',
                        text: 'Le récapitulatif de la commande a bien été envoyé au client.',
                        timer: 2500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Erreur', data.message || "L'envoi de l'e-mail a échoué.", 'error');
                }
            })
            .catch(error => Swal.fire('Erreur', "Une erreur inattendue est survenue.", 'error'))
            .finally(() => {
                sendEmailBtn.disabled = false;
                sendEmailBtn.innerHTML = '<i class="fas fa-envelope me-1"></i> Envoyer par e-mail (PDF)';
            });
        }
    </script>
</body>
</html> 