<?php
// On suppose que $active_page est définie dans la page qui inclut ce fichier.
if (!isset($active_page)) {
    $active_page = ''; // Valeur par défaut pour éviter les erreurs
}
?>
<div class="col-md-3 col-lg-2 sidebar">
    <h3 class="text-center mb-4">Stylish Admin</h3>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo ($active_page == 'dashboard') ? 'active' : ''; ?>" href="dashboard.php"><i class="fas fa-users me-2"></i> Utilisateurs</a>
        <a class="nav-link <?php echo ($active_page == 'products') ? 'active' : ''; ?>" href="products.php"><i class="fas fa-boxes me-2"></i> Produits</a>
        <a class="nav-link <?php echo ($active_page == 'promotions') ? 'active' : ''; ?>" href="promotion.php"><i class="fas fa-tags me-2"></i> Promotions</a>
        <a class="nav-link <?php echo ($active_page == 'orders') ? 'active' : ''; ?>" href="commandes.php"><i class="fas fa-receipt me-2"></i> Commandes</a>
        <li class="nav-item">
            <a class="nav-link <?php echo ($active_page == 'claims') ? 'active' : ''; ?>" href="reclamations.php">
                <i class="fas fa-exclamation-circle me-2"></i> Réclamations
            </a>
        </li>
        <li class="nav-item">
            <a href="avis.php" class="nav-link <?php echo ($active_page == 'reviews') ? 'active' : ''; ?>">
                <i class="fas fa-star me-2"></i> Avis
            </a>
        </li>
        <a class="nav-link <?php echo ($active_page == 'favorites') ? 'active' : ''; ?>" href="favoris.php"><i class="fas fa-heart me-2"></i> Favoris</a>
        <a class="nav-link <?php echo ($active_page == 'coupons') ? 'active' : ''; ?>" href="coupons.php"><i class="fas fa-ticket-alt me-2"></i> Coupons</a>

        <!-- Menu Statistiques -->
        <li class="nav-item">
            <a class="nav-link <?php echo (strpos($active_page, '_stats') !== false) ? 'active' : ''; ?>" href="#statsSubmenu" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo (strpos($active_page, '_stats') !== false) ? 'true' : 'false'; ?>" aria-controls="statsSubmenu">
                <i class="fas fa-chart-bar"></i> Statistiques
            </a>
            <div class="collapse <?php echo (strpos($active_page, '_stats') !== false) ? 'show' : ''; ?>" id="statsSubmenu">
                <ul class="nav flex-column ms-3">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($active_page == 'revenue_stats') ? 'active' : ''; ?>" href="stat_revenue.php">
                            <i class="fas fa-dollar-sign"></i> Chiffre d'affaires
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($active_page == 'favorites_stats') ? 'active' : ''; ?>" href="stat_favorites.php">
                            <i class="fas fa-heart"></i> Favoris
                        </a>
                    </li>
                </ul>
            </div>
        </li>

        <div class="mt-auto pt-3 border-top border-secondary">
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a>
        </div>
    </nav>
</div>

<style>
    .sidebar .nav-link.dropdown-toggle.active {
        color: #fff;
    }
    .sidebar .nav-item-sub .nav-link.active-sub {
        color: #fff;
        font-weight: bold;
    }
</style> 