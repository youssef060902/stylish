<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
    exit();
}
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$note = $input['note'] ?? null;
$commentaire = $input['commentaire'] ?? '';
$user_id = $_SESSION['user_id'];
if (!$id || !$note || $note < 1 || $note > 5 || empty($commentaire)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit();
}
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("UPDATE avis SET note = ?, commentaire = ?, date_modification = NOW() WHERE id = ? AND id_user = ?");
    $stmt->execute([$note, $commentaire, $id, $user_id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Avis modifié avec succès.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Modification impossible.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification.']);
} 