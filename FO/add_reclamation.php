<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: reclamations.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = 'localhost';
    $dbname = 'stylish';
    $username = 'root';
    $password = '';
    
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $id_produit = ($type === 'produit' && !empty($_POST['id_produit'])) ? $_POST['id_produit'] : null;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("INSERT INTO reclamation (id_user, id_produit, type, description, statut) VALUES (?, ?, ?, ?, 'nouveau')");
        $stmt->execute([$user_id, $id_produit, $type, $description]);
        
        $_SESSION['success_message'] = "Votre réclamation a été enregistrée avec succès.";
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Une erreur est survenue lors de l'enregistrement de votre réclamation.";
    }
}

header('Location: reclamations.php');
exit();
?> 