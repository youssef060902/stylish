<?php
session_start();

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

// Récupération des utilisateurs
try {
    $stmt = $pdo->query("SELECT * FROM user ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de récupération des utilisateurs : " . $e->getMessage());
}

$active_page = 'users';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Stylish</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <style>
        .table-responsive {
            margin-top: 20px;
        }
        .user-image {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 10px 0;
        }
        .user-image-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #dee2e6;
        }
        .genre-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        .genre-male {
            background-color: #007bff;
            color: white;
        }
        .genre-female {
            background-color: #e83e8c;
            color: white;
        }
        .table th, .table td {
            vertical-align: middle !important;
            padding: 18px 20px !important;
            font-size: 1.1em;
        }
        tr.user-row { cursor: pointer; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Section Utilisateurs -->
                <div id="users-section" class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="d-flex align-items-center">
                            <h2 class="mb-0">Liste des Utilisateurs</h2>
                            <div class="ms-3 badge bg-primary" style="font-size: 1.1em;">
                                <i class="fas fa-users me-1"></i>
                                <span id="user-count"><?php echo count($users); ?></span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="input-group" style="width: 300px;">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" id="searchUser" class="form-control" placeholder="Rechercher un utilisateur...">
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des utilisateurs -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Photo</th>
                                    <th>Prénom</th>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6">
                                        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 0;">
                                            <div style="background:#f3f3f3;border-radius:50%;width:90px;height:90px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 24px rgba(44,62,80,0.10);margin-bottom:18px;">
                                                <i class="fas fa-users" style="font-size:2.8em;color:#dc3545;"></i>
                                            </div>
                                            <div style="font-size:1.3em;color:#888;font-weight:500;">Aucun utilisateur trouvé</div>
                                            <div style="color:#bbb;font-size:1em;margin-top:8px;">Ajoutez un nouvel utilisateur pour commencer</div>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                <tr class="user-row" data-user='<?php echo json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                    <td>
                                        <?php
                                        if (!empty($user['image']) && strpos($user['image'], 'http') === 0) {
                                            $imagePath = $user['image'];
                                            echo '<img src="' . htmlspecialchars($imagePath) . '" class="user-image" alt="Photo de profil">';
                                        } else {
                                            $initial = strtoupper(mb_substr($user['prenom'], 0, 1));
                                            echo '<div style="width:70px;height:70px;border-radius:50%;background:#dc3545;color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.2em;font-weight:bold;box-shadow:0 2px 4px rgba(0,0,0,0.1);">' . $initial . '</div>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['prenom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-2 btn-edit-user"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete-user" data-user-id="<?php echo $user['id']; ?>"><i class="fas fa-trash"></i></button>
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
    </div>

    <!-- Modal Info Utilisateur -->
    <div class="modal fade" id="userInfoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Informations de l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userInfoContent">
                    <!-- Contenu dynamique -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="user-edit.js"></script>
    <script>
        // Ajout de la recherche dynamique
        document.getElementById('searchUser').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('.user-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const prenom = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const nom = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

                if (prenom.includes(searchValue) || nom.includes(searchValue)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Mise à jour du compteur
            document.getElementById('user-count').textContent = visibleCount;
        });

        // Affichage du pop-up d'infos utilisateur
        const userRows = document.querySelectorAll('tr.user-row');
        userRows.forEach(row => {
            row.addEventListener('click', function() {
                const user = JSON.parse(this.getAttribute('data-user'));
                let imageTag = '';
                if (user.image && user.image.startsWith('http')) {
                    imageTag = `<div class='d-flex justify-content-center mb-4'>
                        <img src="${user.image}" alt="Photo de profil" style="width:130px;height:130px;border-radius:50%;object-fit:cover;box-shadow:0 8px 32px rgba(44,62,80,0.18);border:5px solid #fff;transition:box-shadow .3s;">
                    </div>`;
                } else {
                    imageTag = `<div class='d-flex justify-content-center mb-4'>
                        <img src='https://via.placeholder.com/130x130?text=User' alt='Photo de profil' style='width:130px;height:130px;border-radius:50%;object-fit:cover;box-shadow:0 8px 32px rgba(44,62,80,0.18);border:5px solid #fff;transition:box-shadow .3s;'>
                    </div>`;
                }
                // Badge genre
                let genreBadge = '';
                if (user.genre && user.genre.toLowerCase() === 'homme') {
                    genreBadge = `<span class='badge bg-primary'><i class='fas fa-mars me-1'></i>Homme</span>`;
                } else if (user.genre && user.genre.toLowerCase() === 'femme') {
                    genreBadge = `<span class='badge bg-pink' style='background:#e83e8c'><i class='fas fa-venus me-1'></i>Femme</span>`;
                } else {
                    genreBadge = `<span class='badge bg-secondary'>${user.genre || 'Non spécifié'}</span>`;
                }
                let html = `
                    <div class='container-fluid'>
                        <div class='row justify-content-center'>
                            <div class='col-md-10 col-lg-8'>
                                <div class='card shadow-lg border-0 mb-3' style='border-radius:22px;background:linear-gradient(135deg,#f8fafc 60%,#e3e9f7 100%);'>
                                    <div class='card-body p-4'>
                                        ${imageTag}
                                        <h2 class='text-center mb-2' style='font-weight:800;color:#2c3e50;letter-spacing:1px;'>${user.prenom} ${user.nom}</h2>
                                        <div class='text-center mb-3'>${genreBadge}</div>
                                        <div class='row g-3 mb-2'>
                                            <div class='col-md-6'><i class='fas fa-envelope text-secondary me-2'></i><span class='fw-bold text-secondary'>Email :</span> <a href='mailto:${user.email}' class='text-primary text-decoration-none'>${user.email}</a></div>
                                            <div class='col-md-6'><i class='fas fa-birthday-cake text-secondary me-2'></i><span class='fw-bold text-secondary'>Date de naissance :</span> ${user.date_naissance}</div>
                                            <div class='col-md-6'><i class='fas fa-hourglass-half text-secondary me-2'></i><span class='fw-bold text-secondary'>Âge :</span> ${user.age}</div>
                                            <div class='col-md-6'><i class='fas fa-phone text-secondary me-2'></i><span class='fw-bold text-secondary'>Téléphone :</span> ${user.phone}</div>
                                            <div class='col-md-6'><i class='fas fa-map-marker-alt text-secondary me-2'></i><span class='fw-bold text-secondary'>Adresse :</span> ${user.adresse}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.getElementById('userInfoContent').innerHTML = html;
                var modal = new bootstrap.Modal(document.getElementById('userInfoModal'));
                modal.show();
            });
        });

        document.querySelectorAll('.btn-delete-user').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const userId = this.getAttribute('data-user-id');
                Swal.fire({
                    title: 'Supprimer cet utilisateur ?',
                    text: 'Cette action est irréversible !',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('delete_user.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'id=' + encodeURIComponent(userId)
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Supprimé !', data.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Erreur', data.message, 'error');
                            }
                        })
                        .catch(() => {
                            Swal.fire('Erreur', 'Erreur lors de la communication avec le serveur.', 'error');
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
