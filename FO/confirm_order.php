<?php
session_start();
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

$message = '';
if ($order_id > 0 && !empty($token)) {
    $stmt = $pdo->prepare("SELECT id, statut, confirmation_token FROM commande WHERE id = ?");
    $stmt->execute([$order_id]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($commande && $commande['confirmation_token'] === $token) {
        if ($commande['statut'] === 'confirmé') {
            $message = "<div class='alert alert-info mt-5'>Cette commande est déjà confirmée.</div>";
        } else {
            $stmt = $pdo->prepare("UPDATE commande SET statut = 'confirmé', confirmation_token = NULL WHERE id = ?");
            $stmt->execute([$order_id]);
            $message = "<div class='alert alert-success mt-5'>Merci ! Votre commande a bien été confirmée.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger mt-5'>Lien de confirmation invalide ou commande introuvable.</div>";
    }
} else {
    $message = "<div class='alert alert-danger mt-5'>Paramètres manquants ou invalides.</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation de commande</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="text-center mt-5">Confirmation de commande</h2>
        <?php echo $message; ?>
        <div class="text-center mt-4">
            <a href="mes_commandes.php" class="btn btn-primary">Suivre mes Commandes</a>
        </div>
    </div>
</body>
</html> 