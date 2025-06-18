<?php
?>
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
  <title>Mes réclamations</title>
  <style>
    .reclam-title {
      font-size: 2rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 2rem;
      text-align: center;
    }
    .reclam-empty {
      text-align: center;
      color: #888;
      font-size: 1.2rem;
      margin: 3rem 0;
    }
    .reclam-table th, .reclam-table td {
      vertical-align: middle;
    }
    .add-reclam-btn {
      display: block;
      margin: 0 auto 2rem auto;
      background: #e74c3c;
      color: #fff;
      border: none;
      border-radius: 30px;
      padding: 0.7rem 2rem;
      font-weight: 600;
      font-size: 1.1rem;
      transition: background 0.2s;
    }
    .add-reclam-btn:hover {
      background: #c0392b;
    }
    .status-badge {
      border-radius: 12px;
      padding: 0.4em 1em;
      font-size: 0.95em;
      font-weight: 600;
      color: #fff;
      background: #e67e22;
    }
    .status-badge.traitee { background: #27ae60; }
    .status-badge.refusee { background: #e74c3c; }
  </style>


</head>
<body>
  <section class="container py-5">
    <h2 class="reclam-title">Mes réclamations</h2>
    <?php if (!isset($_SESSION['user_id'])): ?>
      <div class="reclam-empty">Veuillez vous <a href="#" data-bs-toggle="modal" data-bs-target="#modallogin">connecter</a> pour voir vos réclamations.</div>
    <?php else: ?>
      <button class="add-reclam-btn" data-bs-toggle="modal" data-bs-target="#addReclamModal">
        <i class="fas fa-plus me-2"></i>Nouvelle réclamation
      </button>
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
        $stmt = $pdo->prepare("SELECT * FROM reclamation WHERE id_user = ? ORDER BY date_creation DESC");
        $stmt->execute([$user_id]);
        $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch(PDOException $e) {
        $reclamations = [];
      }
      ?>
      <?php if (empty($reclamations)): ?>
        <div class="reclam-empty">Vous n'avez aucune réclamation.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-bordered table-hover reclam-table align-middle">
            <thead class="table-light">
              <tr>
                <th>Objet</th>
                <th>Description</th>
                <th>Date</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reclamations as $rec): ?>
                <tr>
                  <td><?php echo htmlspecialchars($rec['objet']); ?></td>
                  <td><?php echo nl2br(htmlspecialchars($rec['description'])); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($rec['date_creation'])); ?></td>
                  <td>
                    <span class="status-badge <?php echo strtolower($rec['statut']); ?>">
                      <?php echo ucfirst($rec['statut']); ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      <!-- Modal d'ajout de réclamation -->
      <div class="modal fade" id="addReclamModal" tabindex="-1" aria-labelledby="addReclamModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="post" action="add_reclamation.php">
              <div class="modal-header">
                <h5 class="modal-title" id="addReclamModalLabel">Nouvelle réclamation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label for="objet" class="form-label">Objet</label>
                  <input type="text" class="form-control" id="objet" name="objet" required>
                </div>
                <div class="mb-3">
                  <label for="description" class="form-label">Description</label>
                  <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Envoyer</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </section>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/plugins.js"></script>
<script src="js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


</body>
</html> 