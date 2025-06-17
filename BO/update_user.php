<?php
header('Content-Type: application/json');
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

$id = $_POST['id'] ?? null;
$prenom = trim($_POST['prenom'] ?? '');
$nom = trim($_POST['nom'] ?? '');
$genre = $_POST['genre'] ?? '';
$date_naissance = $_POST['date_naissance'] ?? '';
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');
$delete_image = $_POST['delete_image'] ?? '0';

if (!$id || !$prenom || !$nom || !$genre || !$date_naissance || !$email || !$phone || !$adresse) {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires']);
    exit;
}
if (!preg_match('/^[\w\.\-]+@[\w\.\-]+\.[a-z]{2,}$/i', $email)) {
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit;
}
if (!preg_match('/^\d{8,15}$/', preg_replace('/\D/', '', $phone))) {
    echo json_encode(['success' => false, 'message' => 'Téléphone invalide']);
    exit;
}
$birth = new DateTime($date_naissance);
$now = new DateTime();
$age = $now->diff($birth)->y;
if ($age < 18) {
    echo json_encode(['success' => false, 'message' => 'L\'utilisateur doit avoir au moins 18 ans']);
    exit;
}

// Gestion de l'image
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['image']['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Format d\'image non supporté. Utilisez JPG, PNG ou GIF']);
        exit;
    }
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/img/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid('user_') . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
        $image_path = 'http://localhost/img/' . $new_filename;
    }
}

// Si pas d'image uploadée, garder l'ancienne
if (!$image_path) {
    $stmt = $pdo->prepare('SELECT image FROM user WHERE id = ?');
    $stmt->execute([$id]);
    $image_path = $stmt->fetchColumn();
}

if ($delete_image === '1') {
    // Supprimer l'ancienne image du disque si elle existe et n'est pas l'image par défaut
    $stmt = $pdo->prepare('SELECT image FROM user WHERE id = ?');
    $stmt->execute([$id]);
    $old_image = $stmt->fetchColumn();
    if ($old_image && strpos($old_image, 'http://localhost/img/') === 0) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/img/' . basename($old_image);
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }
    $image_path = null; // Ou l'URL de l'image par défaut si tu veux
}

$stmt = $pdo->prepare('UPDATE user SET prenom=?, nom=?, genre=?, date_naissance=?, age=?, email=?, phone=?, adresse=?, image=? WHERE id=?');
$ok = $stmt->execute([$prenom, $nom, $genre, $date_naissance, $age, $email, $phone, $adresse, $image_path, $id]);

if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour avec succès']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
} 