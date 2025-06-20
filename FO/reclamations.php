<?php
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';
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
      font-size: 2.5rem;
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 2.5rem;
      text-align: center;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    .reclam-empty {
      text-align: center;
      color: #95a5a6;
      font-size: 1.3rem;
      margin: 3rem 0;
      padding: 2rem;
      background: #f8f9fa;
      border-radius: 10px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .reclam-table {
      background: white;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .reclam-table th {
      background: #2c3e50;
      color: white;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.9rem;
      letter-spacing: 1px;
      padding: 1.2rem 1rem;
    }
    .reclam-table td {
      padding: 1rem;
      vertical-align: middle;
      font-size: 0.95rem;
    }
    .add-reclam-btn {
      display: block;
      margin: 0 auto 2.5rem auto;
      background: #e74c3c;
      color: #fff;
      border: none;
      border-radius: 30px;
      padding: 1rem 2.5rem;
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s ease;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 4px 6px rgba(231, 76, 60, 0.2);
    }
    .add-reclam-btn:hover {
      background: #c0392b;
      transform: translateY(-2px);
      box-shadow: 0 6px 8px rgba(231, 76, 60, 0.3);
    }
    .status-badge {
      border-radius: 20px;
      padding: 0.5em 1.2em;
      font-size: 0.9em;
      font-weight: 600;
      color: #fff;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .status-badge.nouveau { background: #3498db; }
    .status-badge.en_cours { background: #f1c40f; }
    .status-badge.résolu { background: #2ecc71; }
    .status-badge.fermé { background: #95a5a6; }
    
    .modal-content {
      border-radius: 15px;
      box-shadow: 0 0 30px rgba(0,0,0,0.2);
    }
    .modal-header {
      background: #2c3e50;
      color: white;
      border-top-left-radius: 15px;
      border-top-right-radius: 15px;
      padding: 1.5rem;
    }
    .modal-title {
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: white;
    }
    .modal-body {
      padding: 2rem;
    }
    .form-label {
      font-weight: 600;
      color: #2c3e50;
      margin-bottom: 0.5rem;
    }
    .form-control {
      border-radius: 10px;
      padding: 0.8rem;
      border: 2px solid #eee;
      transition: all 0.3s ease;
    }
    .form-control:focus {
      border-color: #3498db;
      box-shadow: 0 0 0 0.2rem rgba(52,152,219,0.25);
    }
    .pulse {
      animation: pulse 1s infinite;
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
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
      try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("SET NAMES utf8");
        
        // Récupération des réclamations
        $stmt = $pdo->prepare("SELECT r.*, p.nom as nom_produit FROM reclamation r 
                             LEFT JOIN produit p ON r.id_produit = p.id 
                             WHERE r.id_user = ? 
                             ORDER BY r.date_creation DESC");
        $stmt->execute([$user_id]);
        $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des produits commandés par l'utilisateur
        $stmt = $pdo->prepare("SELECT DISTINCT p.id, p.nom 
                             FROM produit p 
                             INNER JOIN commande_produit cp ON p.id = cp.id_produit 
                             INNER JOIN commande c ON cp.id_commande = c.id 
                             WHERE c.id_user = ?");
        $stmt->execute([$user_id]);
        $produits_commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch(PDOException $e) {
        $reclamations = [];
        $produits_commandes = [];
      }
      ?>
      <?php if (empty($reclamations)): ?>
        <div class="reclam-empty">Vous n'avez aucune réclamation.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover reclam-table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Produit</th>
                <th>Description</th>
                <th>Date</th>
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reclamations as $rec): ?>
                <tr>
                  <td><?php echo ucfirst($rec['type']); ?></td>
                  <td><?php echo $rec['nom_produit'] ? htmlspecialchars($rec['nom_produit']) : '-'; ?></td>
                  <td><?php echo nl2br(htmlspecialchars($rec['description'])); ?></td>
                  <td><?php echo date('d/m/Y', strtotime($rec['date_creation'])); ?></td>
                  <td>
                    <span class="status-badge <?php echo $rec['statut']; ?>">
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
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form method="post" action="add_reclamation.php">
              <div class="modal-header">
                <h5 class="modal-title" id="addReclamModalLabel">Nouvelle réclamation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label for="type" class="form-label">Type de réclamation</label>
                  <select class="form-select" id="type" name="type" required>
                    <option value="">Choisir un type</option>
                    <option value="produit">Produit</option>
                    <option value="livraison">Livraison</option>
                    <option value="service">Service</option>
                    <option value="paiement">Paiement</option>
                    <option value="autre">Autre</option>
                  </select>
                </div>
                
                <div class="mb-3" id="produit_commande_div">
                  <label for="id_produit" class="form-label">Produit concerné</label>
                  <select class="form-select" id="id_produit" name="id_produit">
                    <option value="">Sélectionner un produit</option>
                    <?php foreach ($produits_commandes as $produit): ?>
                      <option value="<?php echo $produit['id']; ?>">
                        <?php echo htmlspecialchars($produit['nom']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
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
<script src="js/reclamation.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html> 