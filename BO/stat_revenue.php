<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$active_page = 'revenue_stats';

// Connexion à la base de données pour récupérer les utilisateurs
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les utilisateurs qui ont passé des commandes
    $stmt_users = $pdo->query("
        SELECT DISTINCT u.id, CONCAT(u.prenom, ' ', u.nom) as user_name
        FROM user u
        JOIN commande c ON u.id = c.id_user
        ORDER BY user_name ASC
    ");
    $users_with_orders = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les marques et catégories pour les filtres
    $marques = $pdo->query("SELECT DISTINCT marque FROM produit ORDER BY marque")->fetchAll(PDO::FETCH_COLUMN);
    $categories = $pdo->query("SELECT DISTINCT catégorie FROM produit ORDER BY catégorie")->fetchAll(PDO::FETCH_COLUMN);


} catch (PDOException $e) {
    // Gérer l'erreur de manière non bloquante pour l'affichage de la page
    $users_with_orders = [];
    $marques = [];
    $categories = [];
    $db_error = "Erreur de connexion à la base de données : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques du Chiffre d'Affaires - Stylish</title>
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
        #chartContainer {
            position: relative;
            height: 60vh; /* Hauteur contrôlée */
            width: 100%;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Statistiques du Chiffre d'Affaires</h1>
            </div>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Constructeur de Graphique</h5>
                    <div class="row g-3">
                         <!-- Sélecteurs d'axes -->
                        <div class="col-md-4">
                            <label for="xAxisSelector" class="form-label fw-bold">Axe X (Grouper par)</label>
                            <select id="xAxisSelector" class="form-select builder-control">
                                <option value="p.catégorie">Catégorie Produit</option>
                                <option value="p.marque">Marque Produit</option>
                                <option value="c.statut">Statut Commande</option>
                                <option value="u.id">Utilisateur</option>
                                <option value="on_promotion">Promotion</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="yAxisSelector" class="form-label fw-bold">Axe Y (Calculer)</label>
                            <select id="yAxisSelector" class="form-select builder-control">
                                <option value="ORDER_REVENUE_NET">Revenu Commandes (Net)</option>
                                <option value="SUM_QUANTITY">Nombre de Produits Vendus</option>
                                <option value="COUNT_ORDERS">Nombre de Commandes</option>
                                <option value="AVG_ORDER_VALUE">Panier Moyen (Brut)</option>
                            </select>
                        </div>
                         <div class="col-md-4">
                            <label for="chartType" class="form-label fw-bold">Type de Graphique</label>
                            <select id="chartType" class="form-select builder-control">
                                <option value="bar" selected>Barres</option>
                                <option value="line">Ligne</option>
                                <option value="pie">Circulaire</option>
                                <option value="doughnut">Donut</option>
                            </select>
                        </div>
                    </div>
                    <div id="configWarning"></div>
                    <hr>
                    <h6 class="card-subtitle mb-2 text-muted">Filtres additionnels</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="startDate" class="form-label">Date de début</label>
                            <input type="date" id="startDate" class="form-control builder-control">
                        </div>
                        <div class="col-md-3">
                            <label for="endDate" class="form-label">Date de fin</label>
                            <input type="date" id="endDate" class="form-control builder-control">
                        </div>
                        <div class="col-md-3">
                            <label for="userFilter" class="form-label">Utilisateur</label>
                            <select id="userFilter" class="form-select builder-control">
                                <option value="">Tous</option>
                                <?php foreach($users_with_orders as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['user_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Statut Commande</label>
                            <select id="statusFilter" class="form-select builder-control">
                                <option value="">Tous</option>
                                <option value="en attente">En attente</option>
                                <option value="confirmé">Confirmé</option>
                                <option value="en cours">En cours</option>
                                <option value="livré">Livré</option>
                            </select>
                        </div>
                    </div>
                     <div class="row g-3 mt-1">
                        <div class="col-md-3">
                           <label for="categoryFilter" class="form-label">Catégorie Produit</label>
                            <select id="categoryFilter" class="form-select builder-control">
                                <option value="">Toutes</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="brandFilter" class="form-label">Marque Produit</label>
                            <select id="brandFilter" class="form-select builder-control">
                                <option value="">Toutes</option>
                                <?php foreach($marques as $marque): ?>
                                    <option value="<?php echo htmlspecialchars($marque); ?>"><?php echo htmlspecialchars($marque); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input builder-control" type="checkbox" role="switch" id="promoFilter">
                                <label class="form-check-label" for="promoFilter">En promotion uniquement</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Indicateurs Clés (KPIs) -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card kpi-card">
                        <div class="card-body">
                            <div id="totalRevenue" class="kpi-value">...</div>
                            <div class="kpi-label">Chiffre d'Affaires Total</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card kpi-card">
                        <div class="card-body">
                            <div id="orderCount" class="kpi-value">...</div>
                            <div class="kpi-label">Nombre de Commandes</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card kpi-card">
                        <div class="card-body">
                            <div id="totalProductsSold" class="kpi-value">...</div>
                            <div class="kpi-label">Produits Vendus</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Graphique -->
            <div class="card">
                <div class="card-body">
                    <div id="chartContainer">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // S'assurer que le plugin est bien enregistré
        Chart.register(ChartDataLabels);

        const ctx = document.getElementById('revenueChart').getContext('2d');
        let revenueChart;

        // Génère des couleurs aléatoires pour les graphiques
        function generateColors(numColors) {
            const colors = [];
            for(let i = 0; i < numColors; i++) {
                const r = Math.floor(Math.random() * 220);
                const g = Math.floor(Math.random() * 220);
                const b = Math.floor(Math.random() * 220);
                colors.push(`rgba(${r}, ${g}, ${b}, 0.7)`);
            }
            return colors;
        }
        
        function createOrUpdateChart(data) {
            if (revenueChart) {
                revenueChart.destroy();
            }

            const chartType = document.getElementById('chartType').value;
            const yAxisLabel = document.getElementById('yAxisSelector').selectedOptions[0].text;
            
            const datasets = [{
                label: yAxisLabel,
                data: data.values,
                backgroundColor: generateColors(data.labels.length),
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: chartType === 'line' ? 2 : 1,
                fill: chartType === 'line',
                tension: 0.1
            }];

            const dataLabelsConfig = {
                display: true,
                color: '#333',
                font: {
                    weight: 'bold'
                },
                formatter: (value, context) => {
                    const yAxisKey = document.getElementById('yAxisSelector').value;
                    let formattedValue;

                    if (yAxisKey === 'SUM_REVENUE' || yAxisKey === 'AVG_ORDER_VALUE') {
                        formattedValue = value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' DT';
                    } else {
                        formattedValue = value.toLocaleString('fr-FR');
                    }

                    if (chartType === 'pie' || chartType === 'doughnut') {
                        const allData = context.chart.data.datasets[context.datasetIndex].data;
                        const total = allData.reduce((acc, current) => acc + parseFloat(current), 0);
                        if (total > 0) {
                            const percentage = (value / total * 100).toFixed(1) + '%';
                            return `${formattedValue}\n(${percentage})`;
                        }
                    }
                    
                    return formattedValue;
                }
            };

            // Ajustement pour les graphiques circulaires pour une meilleure lisibilité
            if (chartType === 'pie' || chartType === 'doughnut') {
                dataLabelsConfig.anchor = 'center';
                dataLabelsConfig.align = 'center';
                dataLabelsConfig.color = '#fff';
            } else {
                dataLabelsConfig.anchor = 'end';
                dataLabelsConfig.align = 'end';
            }

            const chartTitle = `${document.getElementById('yAxisSelector').selectedOptions[0].text} par ${document.getElementById('xAxisSelector').selectedOptions[0].text}`;
            const maxValue = data.values.length > 0 ? Math.max(...data.values) : 0;

            revenueChart = new Chart(ctx, {
                type: chartType,
                data: {
                    labels: data.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: chartTitle,
                            font: {
                                size: 18
                            },
                            padding: {
                                bottom: 20
                            }
                        },
                        legend: {
                            display: chartType === 'pie' || chartType === 'doughnut'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        const yAxisKey = document.getElementById('yAxisSelector').value;
                                        if (yAxisKey === 'PRODUCT_REVENUE' || yAxisKey === 'ORDER_REVENUE_NET' || yAxisKey === 'AVG_ORDER_VALUE') {
                                             label += new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'TND' }).format(context.parsed.y);
                                        } else {
                                            label += context.parsed.y;
                                        }
                                    }
                                    return label;
                                }
                            }
                        },
                        datalabels: dataLabelsConfig
                    },
                    scales: { 
                        y: { 
                            beginAtZero: true, 
                            display: chartType !== 'pie' && chartType !== 'doughnut',
                            suggestedMax: maxValue * 1.15 // Ajoute 15% de marge en haut
                        },
                        x: { 
                            display: chartType !== 'pie' && chartType !== 'doughnut' 
                        }
                    }
                }
            });
        }

        function updateStats() {
            const xAxis = document.getElementById('xAxisSelector').value;
            const yAxis = document.getElementById('yAxisSelector').value;
            const chartType = document.getElementById('chartType').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const status = document.getElementById('statusFilter').value;
            const userId = document.getElementById('userFilter').value;

            document.getElementById('totalRevenue').textContent = '...';
            document.getElementById('orderCount').textContent = '...';
            document.getElementById('totalProductsSold').textContent = '...';
            if(revenueChart) { revenueChart.destroy(); }

            const params = new URLSearchParams({
                xAxis: document.getElementById('xAxisSelector').value,
                yAxis: document.getElementById('yAxisSelector').value,
                startDate: document.getElementById('startDate').value,
                endDate: document.getElementById('endDate').value,
                status: document.getElementById('statusFilter').value,
                userId: document.getElementById('userFilter').value,
                brand: document.getElementById('brandFilter').value,
                category: document.getElementById('categoryFilter').value,
                promo: document.getElementById('promoFilter').checked,
            });

            checkCompatibility(); // Affiche un avertissement si nécessaire

            fetch(`get_revenue_data.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    // Mettre à jour les KPIs
                    document.getElementById('totalRevenue').textContent = `${data.kpis.total_revenue.toFixed(2)} DT`;
                    document.getElementById('orderCount').textContent = data.kpis.order_count.toLocaleString('fr-FR');
                    document.getElementById('totalProductsSold').textContent = data.kpis.total_products_sold.toLocaleString('fr-FR');

                    // Mettre à jour le graphique
                    createOrUpdateChart(data.chart_data);
                })
                .catch(error => {
                    console.error("Erreur de chargement des statistiques:", error);
                    document.getElementById('totalRevenue').textContent = 'Erreur';
                    document.getElementById('orderCount').textContent = 'Erreur';
                    document.getElementById('totalProductsSold').textContent = 'Erreur';
                    if (revenueChart) { revenueChart.destroy(); }
                });
        }

        function checkCompatibility() {
            const xAxis = document.getElementById('xAxisSelector').value;
            const yAxis = document.getElementById('yAxisSelector').value;
            const warningDiv = document.getElementById('configWarning');
            
            const productDimensions = ['p.catégorie', 'p.marque', 'on_promotion'];

            if (yAxis === 'ORDER_REVENUE_NET' && productDimensions.includes(xAxis)) {
                warningDiv.innerHTML = `<div class="alert alert-warning mt-2 small"><b>Attention :</b> Le "Revenu Commandes (Net)" ne peut pas être détaillé par produit. Préférez "Revenu Produits" pour une analyse par catégorie, marque ou promotion.</div>`;
            } else {
                warningDiv.innerHTML = '';
            }
        }

        document.querySelectorAll('.builder-control').forEach(el => {
            el.addEventListener('change', updateStats);
        });

        updateStats(); // Charger les stats initiales
    });
</script>
</body>
</html> 