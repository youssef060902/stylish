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
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
    exit;
}
// Supprimer l'image du disque si présente
$stmt = $pdo->prepare('SELECT image FROM user WHERE id = ?');
$stmt->execute([$id]);
$image = $stmt->fetchColumn();
if ($image && strpos($image, 'http://localhost/img/') === 0) {
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/img/' . basename($image);
    if (file_exists($file_path)) {
        @unlink($file_path);
    }
}
// Supprimer l'utilisateur
$stmt = $pdo->prepare('DELETE FROM user WHERE id = ?');
$ok = $stmt->execute([$id]);
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
} 