<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès interdit']);
    exit();
}

// --- Mappage sécurisé des dimensions et mesures ---
$dimensions = [
    'p.catégorie' => ['expression' => 'catégorie', 'requires' => ['produit']],
    'p.marque' => ['expression' => 'marque', 'requires' => ['produit']],
    'c.statut' => ['expression' => 'statut', 'requires' => []],
    'u.id' => ['expression' => 'user_name', 'requires' => ['user']],
    'on_promotion' => ['expression' => 'on_promotion', 'requires' => ['produit']],
    'date_commande' => ['expression' => 'date_group', 'requires' => []],
    'date_livraison' => ['expression' => 'date_livraison_group', 'requires' => []],
];

$measures = [
    'PRODUCT_REVENUE' => ['expression' => 'product_line_revenue'],
    'ORDER_REVENUE_NET' => ['expression' => 'order_total_net'],
    'SUM_QUANTITY' => ['expression' => 'quantite'],
    'COUNT_ORDERS' => ['expression' => 'order_id'], // Will be counted distinct
    'AVG_ORDER_VALUE' => ['expression' => 'order_total_net'] // Will be averaged
];

// --- Récupération et validation des entrées ---
$x_axis_key = $_GET['xAxis'] ?? 'p.catégorie';
$y_axis_key = $_GET['yAxis'] ?? 'PRODUCT_REVENUE';
$date_type = $_GET['dateType'] ?? 'date_commande';
$date_column = ($date_type === 'date_livraison') ? 'c.date_livraison' : 'c.date_commande';

if (!isset($dimensions[$x_axis_key]) || !isset($measures[$y_axis_key])) {
    http_response_code(400);
    die(json_encode(['error' => 'Paramètres invalides.']));
}
$x_axis = $dimensions[$x_axis_key];
$y_axis = $measures[$y_axis_key];


// --- Connexion DB ---
$host = 'localhost'; $dbname = 'stylish'; $username = 'root'; $password = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) { http_response_code(500); die(json_encode(['error' => "DB Error"])); }

// --- Étape 1: Requête Maître pour récupérer toutes les données brutes nécessaires ---
$from_clause = 'commande c 
                INNER JOIN commande_produit cp ON c.id = cp.id_commande
                INNER JOIN produit p ON cp.id_produit = p.id
                INNER JOIN user u ON c.id_user = u.id';

$params = [];
$where_clauses = ['1=1'];
if (!empty($_GET['startDate'])) { $where_clauses[] = "$date_column >= :startDate"; $params[':startDate'] = $_GET['startDate']; }
if (!empty($_GET['endDate'])) { $endDate = new DateTime($_GET['endDate']); $endDate->modify('+1 day'); $where_clauses[] = "$date_column < :endDate"; $params[':endDate'] = $endDate->format('Y-m-d'); }
if (!empty($_GET['status'])) { $where_clauses[] = "c.statut = :status"; $params[':status'] = $_GET['status']; }
if (!empty($_GET['userId'])) { $where_clauses[] = "c.id_user = :userId"; $params[':userId'] = $_GET['userId']; }
if (!empty($_GET['category'])) { $where_clauses[] = "p.catégorie = :category"; $params[':category'] = $_GET['category']; }
if (!empty($_GET['brand'])) { $where_clauses[] = "p.marque = :brand"; $params[':brand'] = $_GET['brand']; }
if (isset($_GET['promo']) && $_GET['promo'] === 'true') { $where_clauses[] = "p.id_promotion IS NOT NULL"; }
if (!empty($_GET['pointure'])) { $where_clauses[] = "cp.id_pointure = :pointureId"; $params[':pointureId'] = $_GET['pointure']; }
if ($date_type === 'date_livraison') { $where_clauses[] = "c.statut = 'livré'"; }

$where_sql = implode(' AND ', $where_clauses);

$master_sql = "
    SELECT 
        c.id as order_id, c.total as order_total_gross, (c.total - 7) as order_total_net, c.statut,
        cp.quantite, (cp.prix_unitaire * cp.quantite) as product_line_revenue,
        p.catégorie, p.marque, CASE WHEN p.id_promotion IS NOT NULL THEN 'En Promotion' ELSE 'Sans Promotion' END as on_promotion,
        CONCAT(u.prenom, ' ', u.nom) as user_name,
        DATE_FORMAT(c.date_commande, '%Y-%m-%d') as date_group,
        DATE_FORMAT(c.date_livraison, '%Y-%m-%d') as date_livraison_group
    FROM {$from_clause}
    WHERE {$where_sql}
";

$stmt = $pdo->prepare($master_sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Étape 2: Calcul des KPIs et agrégation pour le graphique à partir des données brutes ---

// KPIs directs sur les résultats filtrés
$total_products_sold = array_sum(array_column($results, 'quantite'));
$order_count = count(array_unique(array_column($results, 'order_id')));

// Calcul du revenu total selon l'ancienne méthode (somme des totaux des commandes uniques)
$total_revenue_net = 0;
$processed_orders_for_revenue = [];
foreach ($results as $row) {
    if (!isset($processed_orders_for_revenue[$row['order_id']])) {
        $total_revenue_net += $row['order_total_net'];
        $processed_orders_for_revenue[$row['order_id']] = true;
    }
}

$kpis = [
    'total_revenue_net' => $total_revenue_net,
    'order_count' => $order_count,
    'total_products_sold' => $total_products_sold
];

$chart_aggregation = [];

foreach($results as $row) {
    // Agrégation pour le graphique
    $label = $row[$x_axis['expression']];
    if (!isset($chart_aggregation[$label])) {
        $chart_aggregation[$label] = [];
    }
    // Stocker toutes les valeurs nécessaires pour chaque label
    $chart_aggregation[$label][] = $row;
}


// --- Étape 3: Finaliser le calcul du graphique basé sur la mesure choisie ---
$chart_labels = [];
$chart_values = [];

foreach($chart_aggregation as $label => $rows) {
    $value = 0;
    switch($y_axis_key) {
        case 'PRODUCT_REVENUE':
        case 'SUM_QUANTITY':
            $value = array_sum(array_column($rows, $y_axis['expression']));
            break;
        case 'COUNT_ORDERS':
            $value = count(array_unique(array_column($rows, 'order_id')));
            break;
        case 'ORDER_REVENUE_NET':
        case 'AVG_ORDER_VALUE':
             // Pour ces cas, il faut dé-dupliquer par commande
            $unique_orders = [];
            foreach($rows as $r) {
                $unique_orders[$r['order_id']] = $r[$y_axis['expression']];
            }
            if ($y_axis_key === 'AVG_ORDER_VALUE' && count($unique_orders) > 0) {
                 $value = array_sum($unique_orders) / count($unique_orders);
            } else {
                 $value = array_sum($unique_orders);
            }
            break;
    }
    $chart_labels[] = $label;
    $chart_values[] = $value;
}


// --- Étape 4: Formatage de la réponse JSON ---
$response = [
    'kpis' => [
        'total_revenue' => $kpis['total_revenue_net'],
        'order_count' => $kpis['order_count'],
        'total_products_sold' => $kpis['total_products_sold'],
    ],
    'chart_data' => [
        'labels' => $chart_labels,
        'values' => $chart_values,
    ]
];

echo json_encode($response);
?> 