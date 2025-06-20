<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: reclamations.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $host = 'localhost';
    $dbname = 'stylish';
    $username = 'root';
    $password = '';
    $user_id = $_SESSION['user_id'];
    $id = $_POST['id'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $id_produit = (isset($_POST['type']) && $_POST['type'] === 'produit' && !empty($_POST['id_produit'])) ? $_POST['id_produit'] : null;

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Vérifier que la réclamation appartient à l'utilisateur et est modifiable
        $stmt = $pdo->prepare("SELECT * FROM reclamation WHERE id = ? AND id_user = ? AND statut IN ('nouveau', 'en cours')");
        $stmt->execute([$id, $user_id]);
        $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($reclamation) {
            $stmt = $pdo->prepare("UPDATE reclamation SET type = ?, id_produit = ?, description = ?, date_modification = NOW() WHERE id = ?");
            if ($id_produit === null) {
                $stmt->bindValue(1, $type, PDO::PARAM_STR);
                $stmt->bindValue(2, null, PDO::PARAM_NULL);
                $stmt->bindValue(3, $description, PDO::PARAM_STR);
                $stmt->bindValue(4, $id, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $stmt->execute([$type, $id_produit, $description, $id]);
            }
            $_SESSION['success_message'] = "Réclamation modifiée avec succès.";
        } else {
            $_SESSION['error_message'] = "Modification non autorisée.";
        }
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la modification.";
    }
}
header('Location: reclamations.php');
exit(); 