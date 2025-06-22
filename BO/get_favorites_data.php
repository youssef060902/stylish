<?php
header('Content-Type: application/json');

// Connexion à la base de données
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- Récupération des paramètres ---
    $xAxis = $_GET['xAxis'] ?? 'produit';
    $userId = $_GET['userId'] ?? null;
    $productId = $_GET['productId'] ?? null;
    
    // --- Construction de la requête principale ---
    $query = "
        SELECT 
            f.id_produit, 
            f.id_user,
            p.nom AS nom_produit, 
            p.catégorie AS categorie_produit,
            p.marque AS marque_produit,
            p.couleur AS couleur_produit,
            CONCAT(u.prenom, ' ', u.nom) AS nom_user
        FROM favoris f
        JOIN produit p ON f.id_produit = p.id
        JOIN user u ON f.id_user = u.id
    ";

    $where = [];
    $params = [];
    
    if (!empty($userId)) {
        $where[] = "f.id_user = :userId";
        $params[':userId'] = $userId;
    }
    if (!empty($productId)) {
        $where[] = "f.id_produit = :productId";
        $params[':productId'] = $productId;
    }

    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $allFavorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Calcul des KPIs ---
    $totalFavorites = count($allFavorites);
    $activeUsers = count(array_unique(array_column($allFavorites, 'id_user')));
    
    $productCounts = array_count_values(array_column($allFavorites, 'nom_produit'));
    arsort($productCounts);
    $mostFavoritedProduct = key($productCounts);

    // --- Préparation des données pour le graphique ---
    $labels = [];
    $values = [];
    $groupedData = [];

    if (!empty($allFavorites)) {
        $columnMap = [
            'produit' => 'nom_produit',
            'categorie' => 'categorie_produit',
            'marque' => 'marque_produit',
            'couleur' => 'couleur_produit',
            'user' => 'nom_user'
        ];
        $groupKey = $columnMap[$xAxis] ?? 'nom_produit';
        
        foreach ($allFavorites as $fav) {
            $key = $fav[$groupKey];
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = 0;
            }
            $groupedData[$key]++;
        }
    }
    
    arsort($groupedData);
    $groupedData = array_slice($groupedData, 0, 10, true);

    $labels = array_keys($groupedData);
    $values = array_values($groupedData);

    // --- Envoi de la réponse JSON ---
    echo json_encode([
        'kpis' => [
            'total_favorites' => $totalFavorites,
            'most_favorited_product' => $totalFavorites > 0 ? $mostFavoritedProduct : 'N/A',
            'active_users' => $activeUsers
        ],
        'chart_data' => [
            'labels' => $labels,
            'values' => $values
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données : ' . $e->getMessage()]);
}
?> 