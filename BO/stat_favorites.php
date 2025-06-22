<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$active_page = 'favorites_stats';

// Connexion à la base de données pour récupérer les filtres
$host = 'localhost'; $dbname = 'stylish'; $username = 'root'; $password = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $users = $pdo->query("SELECT DISTINCT u.id, CONCAT(u.prenom, ' ', u.nom) as user_name FROM user u JOIN favoris f ON u.id = f.id_user ORDER BY user_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $products = $pdo->query("SELECT DISTINCT p.id, p.nom FROM produit p JOIN favoris f ON p.id = f.id_produit ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $users = [];
    $products = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques des Favoris - Stylish</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.1.0"></script>
    <style>
        .kpi-card { text-align: center; }
        .kpi-card .kpi-value { font-size: 2.5rem; font-weight: bold; }
        .kpi-card .kpi-label { font-size: 1rem; color: #6c757d; }
        #chartContainer { position: relative; height: 60vh; width: 100%; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Statistiques des Favoris</h1>
            </div>

            <!-- Constructeur & Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="xAxisSelector" class="form-label fw-bold">Axe X (Top 10 par)</label>
                            <select id="xAxisSelector" class="form-select builder-control">
                                <option value="produit">Produit</option>
                                <option value="categorie">Catégorie</option>
                                <option value="marque">Marque</option>
                                <option value="couleur">Couleur</option>
                                <option value="user">Utilisateur</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="chartType" class="form-label fw-bold">Type de Graphique</label>
                            <select id="chartType" class="form-select builder-control">
                                <option value="bar" selected>Barres</option>
                                <option value="pie">Circulaire</option>
                                <option value="doughnut">Donut</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <h6 class="card-subtitle mb-2 text-muted">Filtres additionnels</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="userFilter" class="form-label">Filtrer par Utilisateur</label>
                            <select id="userFilter" class="form-select builder-control">
                                <option value="">Tous</option>
                                <?php foreach($users as $user) { echo "<option value='{$user['id']}'>".htmlspecialchars($user['user_name'])."</option>"; } ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="productFilter" class="form-label">Filtrer par Produit</label>
                            <select id="productFilter" class="form-select builder-control">
                                <option value="">Tous</option>
                                <?php foreach($products as $product) { echo "<option value='{$product['id']}'>".htmlspecialchars($product['nom'])."</option>"; } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card kpi-card">
                        <div class="card-body">
                            <div id="totalFavorites" class="kpi-value">...</div>
                            <div class="kpi-label">Total des Favoris</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card kpi-card">
                        <div class="card-body">
                            <div id="mostFavoritedProduct" class="kpi-value">...</div>
                            <div class="kpi-label">Produit le Plus Populaire</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card kpi-card">
                        <div class="card-body">
                            <div id="activeUsers" class="kpi-value">...</div>
                            <div class="kpi-label">Utilisateurs Actifs (Favoris)</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Graphique -->
            <div class="card">
                <div class="card-body">
                    <div id="chartContainer"><canvas id="favoritesChart"></canvas></div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.register(ChartDataLabels);
    const ctx = document.getElementById('favoritesChart').getContext('2d');
    let favoritesChart;

    function createOrUpdateChart(data) {
        if (favoritesChart) favoritesChart.destroy();
        const chartType = document.getElementById('chartType').value;
        const xAxisLabel = document.getElementById('xAxisSelector').selectedOptions[0].text;
        
        const isBarChart = chartType === 'bar';
        const total = data.values.reduce((a, b) => a + b, 0);
        const maxValue = Math.max(...data.values);

        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: isBarChart ? 'y' : 'x',
            plugins: {
                title: {
                    display: true,
                    text: `Top 10 des ${xAxisLabel} par Nombre de Favoris`,
                    font: { size: 18 },
                    padding: { bottom: 20 }
                },
                legend: {
                    display: !isBarChart,
                    position: 'top'
                },
                datalabels: {
                    anchor: isBarChart ? 'end' : 'center',
                    align: isBarChart ? 'end' : 'center',
                    color: isBarChart ? '#444' : '#fff',
                    font: { weight: 'bold' },
                    formatter: (value) => {
                        if (isBarChart) {
                            return value;
                        }
                        return total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                    }
                }
            }
        };

        if (isBarChart) {
            chartOptions.scales = {
                x: {
                    suggestedMax: maxValue * 1.2
                }
            };
        }

        favoritesChart = new Chart(ctx, {
            type: chartType,
            data: { 
                labels: data.labels, 
                datasets: [{ 
                    label: `Nombre de favoris`, 
                    data: data.values, 
                    backgroundColor: generateColors(data.labels.length) 
                }] 
            },
            options: chartOptions
        });
    }

    function generateColors(num) {
        const colors = [];
        for(let i=0; i<num; i++) {
            const r = Math.floor(Math.random() * 200);
            const g = Math.floor(Math.random() * 200);
            const b = Math.floor(Math.random() * 200);
            colors.push(`rgba(${r}, ${g}, ${b}, 0.8)`);
        }
        return colors;
    }

    function updateStats() {
        const xAxis = document.getElementById('xAxisSelector').value;
        const userId = document.getElementById('userFilter').value;
        const productId = document.getElementById('productFilter').value;

        // Reset KPIs
        document.getElementById('totalFavorites').textContent = '...';
        document.getElementById('mostFavoritedProduct').textContent = '...';
        document.getElementById('activeUsers').textContent = '...';
        
        const params = new URLSearchParams({ xAxis, userId, productId });
        fetch(`get_favorites_data.php?${params}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('totalFavorites').textContent = data.kpis.total_favorites;
                document.getElementById('mostFavoritedProduct').textContent = data.kpis.most_favorited_product || 'N/A';
                document.getElementById('activeUsers').textContent = data.kpis.active_users;
                createOrUpdateChart(data.chart_data);
            })
            .catch(error => console.error("Erreur de chargement des statistiques:", error));
    }

    document.querySelectorAll('.builder-control').forEach(el => el.addEventListener('change', updateStats));
    updateStats();
});
</script>
</body>
</html> 