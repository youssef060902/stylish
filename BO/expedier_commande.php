<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

$response = ['success' => false, 'message' => 'Erreur initiale.'];

if (!isset($_SESSION['admin_id'])) {
    $response['message'] = 'Accès interdit.';
    echo json_encode($response);
    exit();
}

if (!isset($_POST['id'])) {
    $response['message'] = 'ID de commande manquant.';
    echo json_encode($response);
    exit();
}

$order_id = filter_var($_POST['id'], FILTER_VALIDATE_INT);

if (!$order_id) {
    $response['message'] = 'ID de commande invalide.';
    echo json_encode($response);
    exit();
}

try {
    // Connexion DB
    $host = 'localhost';
    $dbname = 'stylish';
    $username = 'root';
    $password = '';
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Mettre à jour la commande
    $stmt_update = $pdo->prepare(
        "UPDATE commande SET statut = 'expédié' WHERE id = :id AND statut = 'en préparation'"
    );
    $stmt_update->execute(['id' => $order_id]);

    if ($stmt_update->rowCount() === 0) {
        throw new Exception('La commande n\'a pas pu être mise à jour. Est-elle bien au statut "en préparation" ?');
    }

    // 2. Récupérer les informations pour l'e-mail
    $stmt_order = $pdo->prepare("SELECT c.*, u.prenom, u.nom, u.email FROM commande c JOIN user u ON c.id_user = u.id WHERE c.id = :id");
    $stmt_order->execute(['id' => $order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Impossible de retrouver les informations de la commande après mise à jour.');
    }

    // 3. Envoyer l'e-mail de notification
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'youssefcarma@gmail.com';
    $mail->Password   = 'oupl cahg lkac cxun';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('no-reply@stylish.com', 'Stylish');
    $mail->addAddress($order['email'], $order['prenom'] . ' ' . $order['nom']);

    $mail->isHTML(true);
    $mail->Subject = 'Votre commande Stylish #' . $order_id . ' a été expédiée !';
    
    $tracking_link = 'http://localhost/stylish/FO/suivi_commande.php?id_commande=' . $order_id;

    $emailBody = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Votre commande a été expédiée</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f4f4; color: #333;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; margin-top: 20px; margin-bottom: 20px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <tr>
                <td align="center" style="padding: 40px 0 30px 0; background-color: #000000; color: #ffffff; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    <img src="https://i.ibb.co/nNPjZ5fK/Chat-GPT-Image-8-juin-2025-23-27-31.png" alt="Stylish Logo" width="150" style="display: block;">
                </td>
            </tr>
            <tr>
                <td style="padding: 40px 30px;">
                    <h1 style="font-size: 24px; margin: 0; margin-bottom: 20px;">Excellente nouvelle, ' . htmlspecialchars($order['prenom']) . ' !</h1>
                    <p style="margin: 0 0 25px 0; font-size: 16px; line-height: 1.5;">Votre commande <strong>#' . $order_id . '</strong> est en route. Préparez-vous à la recevoir !</p>
                </td>
            </tr>
            <tr>
                <td align="center" style="padding: 0 30px 40px 30px;">
                    <table border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="center" style="border-radius: 5px;" bgcolor="#28a745">
                                <a href="' . $tracking_link . '" target="_blank" style="font-size: 16px; color: #ffffff; text-decoration: none; padding: 15px 25px; border-radius: 5px; display: inline-block; font-weight: bold;">Suivre mon colis</a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td bgcolor="#f4f4f4" style="padding: 30px 30px; text-align: center; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                    <p style="margin: 0; color: #888; font-size: 12px;">Merci pour votre confiance !<br>&copy; ' . date('Y') . ' Stylish Store. Tous droits réservés.</p>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    $mail->Body = $emailBody;
    $mail->send();
    
    $response['success'] = true;
    $response['message'] = 'Commande marquée comme expédiée et e-mail envoyé.';

} catch (Exception $e) {
    // Log de l'erreur pour une maintenance future, sans l'afficher au client
    error_log('Erreur dans expedier_commande.php: ' . $e->getMessage());
    $response['message'] = 'Une erreur technique est survenue. L\'administrateur a été notifié.';
}

echo json_encode($response);
exit();
?> 