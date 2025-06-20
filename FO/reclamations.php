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
                <th>Date de création</th>
                <th>Statut</th>
                <th>Actions</th>
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
                  <td>
                    <?php if (in_array($rec['statut'], ['nouveau', 'en cours'])): ?>
                      <button class="btn btn-sm btn-warning edit-reclam-btn" 
                        data-id="<?php echo $rec['id']; ?>"
                        data-type="<?php echo htmlspecialchars(trim(strtolower($rec['type']))); ?>"
                        data-id_produit="<?php echo $rec['id_produit'] !== null ? $rec['id_produit'] : ''; ?>"
                        data-description="<?php echo htmlspecialchars($rec['description']); ?>"
                        data-bs-toggle="modal" data-bs-target="#editReclamModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a4 4 0 01-1.414.828l-4.243 1.414 1.414-4.243a4 4 0 01.828-1.414z"/></svg>
                      </button>
                      <form method="post" action="delete_reclamation.php" style="display:inline;">
                        <input type="hidden" name="id" value="<?php echo $rec['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réclamation ?');">
                          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                      </form>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
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
      <!-- Modal d'édition de réclamation -->
      <div class="modal fade" id="editReclamModal" tabindex="-1" aria-labelledby="editReclamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form method="post" action="update_reclamation.php">
              <input type="hidden" name="id" id="edit_id">
              <div class="modal-header">
                <h5 class="modal-title" id="editReclamModalLabel">Modifier la réclamation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label for="edit_type" class="form-label">Type de réclamation</label>
                  <select class="form-select" id="edit_type" name="type" required>
                    <option value="">Choisir un type</option>
                    <option value="produit">Produit</option>
                    <option value="livraison">Livraison</option>
                    <option value="service">Service</option>
                    <option value="paiement">Paiement</option>
                    <option value="autre">Autre</option>
                  </select>
                </div>
                <div class="mb-3" id="edit_produit_commande_div">
                  <label for="edit_id_produit" class="form-label">Produit concerné</label>
                  <select class="form-select" id="edit_id_produit" name="id_produit">
                    <option value="">Sélectionner un produit</option>
                    <?php foreach ($produits_commandes as $produit): ?>
                      <option value="<?php echo $produit['id']; ?>">
                        <?php echo htmlspecialchars($produit['nom']); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label for="edit_description" class="form-label">Description</label>
                  <textarea class="form-control" id="edit_description" name="description" rows="4" required></textarea>
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
    <?php endif; ?>
  </section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/plugins.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>$(document).ready(function() {
    // Cache le select des produits par défaut
    $('#produit_commande_div').hide();
    
    // Gestion du changement de type de réclamation
    $('#type').on('change', function() {
        if ($(this).val() === 'produit') {
            $('#produit_commande_div').slideDown();
            $('#id_produit').prop('required', true);
        } else {
            $('#produit_commande_div').slideUp();
            $('#id_produit').prop('required', false);
        }
    });

    // Animation du formulaire
    $('.add-reclam-btn').hover(
        function() { $(this).addClass('pulse'); },
        function() { $(this).removeClass('pulse'); }
    );

    // Effet de transition sur les status badges
    $('.status-badge').each(function() {
        $(this).css('transition', 'all 0.3s ease');
    });

    // Gestion du modal d'édition
    $('.edit-reclam-btn').on('click', function() {
        const id = $(this).data('id');
        console.log('ID de la réclamation:', id); // Debug
        $("#editReclamModal button[type='submit']").prop('disabled', true);
        $('#edit_id').val('');
        $('#edit_type').val('');
        $('#edit_description').val('');
        $('#edit_id_produit').val('');
        $('#edit_produit_commande_div').hide();
    
        $.get('get_reclamation.php', {id: id}, function(response) {
            console.log('Réponse AJAX:', response); // Debug
            if (response.success) {
                console.log('Données reçues:', response.data); // Debug
                $('#edit_id').val(response.data.id);
                $('#edit_type').val(response.data.type);
                $('#edit_description').val(response.data.description);
                if (response.data.type === 'produit') {
                    $('#edit_produit_commande_div').show();
                    $('#edit_id_produit').val(response.data.id_produit || '');
                    $('#edit_id_produit').prop('required', true);
                } else {
                    $('#edit_produit_commande_div').hide();
                    $('#edit_id_produit').val('').prop('required', false);
                }
            } else {
                alert('Erreur lors de la récupération de la réclamation : ' + (response.message || 'Aucune donnée reçue.'));
            }
            $("#editReclamModal button[type='submit']").prop('disabled', false);
        }, 'json').fail(function(jqXHR, textStatus, errorThrown) {
            console.log('Erreur AJAX:', jqXHR, textStatus, errorThrown); // Debug
            alert('Erreur AJAX : ' + textStatus);
            $("#editReclamModal button[type='submit']").prop('disabled', false);
        });
    });

    // Affichage dynamique du select produit dans le modal d'édition
    $('#edit_type').on('change', function() {
        if ($(this).val() === 'produit') {
            $('#edit_produit_commande_div').slideDown();
            $('#edit_id_produit').prop('required', true);
        } else {
            $('#edit_produit_commande_div').slideUp();
            $('#edit_id_produit').prop('required', false);
        }
    });
}); </script>
</body>
</html> 