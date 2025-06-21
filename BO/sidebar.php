<?php
// On suppose que $active_page est définie dans la page qui inclut ce fichier.
if (!isset($active_page)) {
    $active_page = ''; // Valeur par défaut pour éviter les erreurs
}
?>
<div class="col-md-3 col-lg-2 sidebar">
    <h3 class="text-center mb-4">Stylish Admin</h3>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
        <a class="nav-link <?php echo ($active_page == 'users') ? 'active' : ''; ?>" href="dashboard.php"><i class="fas fa-users me-2"></i> Utilisateurs</a>
        <a class="nav-link <?php echo ($active_page == 'products') ? 'active' : ''; ?>" href="products.php"><i class="fas fa-boxes me-2"></i> Produits</a>
        <a class="nav-link <?php echo ($active_page == 'promotions') ? 'active' : ''; ?>" href="promotion.php"><i class="fas fa-tags me-2"></i> Promotions</a>
        <a class="nav-link <?php echo ($active_page == 'orders') ? 'active' : ''; ?>" href="commandes.php"><i class="fas fa-receipt me-2"></i> Commandes</a>
        <li class="nav-item">
            <a class="nav-link <?php echo ($active_page == 'claims') ? 'active' : ''; ?>" href="reclamations.php">
                <i class="fas fa-exclamation-circle me-2"></i> Réclamations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($active_page == 'reviews') ? 'active' : ''; ?>" href="avis.php">
                <i class="fas fa-star me-2"></i> Avis
            </a>
        </li>
        <a class="nav-link <?php echo ($active_page == 'favorites') ? 'active' : ''; ?>" href="favoris.php"><i class="fas fa-heart me-2"></i> Favoris</a>
        <div class="mt-auto pt-3 border-top border-secondary">
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </nav>
</div> 