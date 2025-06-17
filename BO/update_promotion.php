<?php
session_start();

// Vérification de l'authentification
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
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
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit();
}

// Vérification des données reçues
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupération et validation des données
$id = intval($_POST['id'] ?? 0);
$nom = trim($_POST['nom'] ?? '');
$description = trim($_POST['description'] ?? '');
$date_debut = $_POST['date_debut'] ?? '';
$date_fin = $_POST['date_fin'] ?? '';
$discount = intval($_POST['discount'] ?? 0);

// Validation des données
if ($id <= 0 || empty($nom) || empty($description) || empty($date_debut) || empty($date_fin) || $discount <= 0 || $discount >= 100) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis et la réduction doit être entre 1 et 99%']);
    exit();
}

// Validation des dates
$tz_tunis = new DateTimeZone('Africa/Tunis');
$date_debut_obj = new DateTime($date_debut, $tz_tunis);
$date_fin_obj = new DateTime($date_fin, $tz_tunis);

if ($date_fin_obj <= $date_debut_obj) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'La date de fin doit être postérieure à la date de début']);
    exit();
}

try {
    // Vérification de l'existence de la promotion
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM promotion WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() == 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Promotion non trouvée']);
        exit();
    }

    // Vérification des chevauchements de dates (en excluant la promotion actuelle)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM promotion WHERE id != ? AND (
        (date_debut <= ? AND date_fin >= ?) OR 
        (date_debut <= ? AND date_fin >= ?) OR 
        (date_debut >= ? AND date_fin <= ?)
    )");
    
    $stmt->execute([$id, $date_fin, $date_debut, $date_fin, $date_debut, $date_debut, $date_fin]);
    
    if ($stmt->fetchColumn() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Une autre promotion existe déjà sur cette période']);
        exit();
    }

    // Mise à jour de la promotion
    $stmt = $pdo->prepare("UPDATE promotion SET nom = ?, description = ?, date_debut = ?, date_fin = ?, discount = ? WHERE id = ?");
    $stmt->execute([$nom, $description, $date_debut, $date_fin, $discount, $id]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Promotion mise à jour avec succès']);

} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour de la promotion']);
} 