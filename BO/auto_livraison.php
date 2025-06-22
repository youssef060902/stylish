<?php
// Script à lancer en cron chaque heure ou chaque jour
// Passe automatiquement les commandes à 'livré' si la date_livraison est dépassée (après 18h)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../vendor/autoload.php';

$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Heure actuelle
    $now = new DateTime('now', new DateTimeZone('Africa/Tunis'));
    $today = $now->format('Y-m-d');
    $heure = $now->format('H:i:s');

    // Sélectionner les commandes concernées AVANT la mise à jour
    $sql_select = "
        SELECT c.id, c.date_livraison, u.prenom, u.email
        FROM commande c
        JOIN user u ON c.id_user = u.id
        WHERE (c.statut = 'expédié' OR c.statut = 'en préparation')
        AND (
            (DATE(c.date_livraison) < :today)
            OR (DATE(c.date_livraison) = :today AND TIME(:heure) >= '18:00:00')
        )
    ";
    $stmt_sel = $pdo->prepare($sql_select);
    $stmt_sel->execute(['today' => $today, 'heure' => $heure]);
    $orders = $stmt_sel->fetchAll(PDO::FETCH_ASSOC);

    // Mise à jour des statuts
    $sql = "
        UPDATE commande
        SET statut = 'livré', date_livraison = IF(date_livraison IS NULL, NOW(), date_livraison)
        WHERE (statut = 'expédié' OR statut = 'en préparation')
        AND (
            (DATE(date_livraison) < :today)
            OR (DATE(date_livraison) = :today AND TIME(:heure) >= '18:00:00')
        )
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['today' => $today, 'heure' => $heure]);
    $count = $stmt->rowCount();

    echo "<h3>Auto-livraison terminée</h3>";
    echo "<p>Commandes passées à 'livré' : <b>$count</b></p>";
    echo "<p>Date/heure d'exécution : " . $now->format('d/m/Y H:i:s') . "</p>";

    // Envoi des e-mails
    if ($count > 0 && $orders) {
        foreach ($orders as $order) {
            try {
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
                $mail->addAddress($order['email'], $order['prenom']);
                $mail->isHTML(true);
                $mail->Subject = 'Bonne réception de votre commande Stylish';
                $date_affiche = $order['date_livraison'] ? date('d/m/Y', strtotime($order['date_livraison'])) : $today;
                $mail->Body = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commande livrée</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background-color:#f4f4f4;color:#333;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse:collapse;margin-top:20px;margin-bottom:20px;background-color:#fff;border-radius:8px;box-shadow:0 4px 8px rgba(0,0,0,0.08);">
        <tr>
            <td align="center" style="padding:40px 0 30px 0;background-color:#27ae60;color:#fff;border-top-left-radius:8px;border-top-right-radius:8px;">
                <img src="https://i.ibb.co/vvZBxfg5/logoo.png" alt="Stylish Logo" width="150" style="display:block;">
            </td>
        </tr>
        <tr>
            <td style="padding:40px 30px;">
                <h1 style="font-size:22px;margin:0 0 18px 0;">Bonne réception, ' . htmlspecialchars($order['prenom']) . ' !</h1>
                <p style="font-size:16px;line-height:1.6;margin:0 0 18px 0;">Nous avons le plaisir de vous informer que votre commande <strong>#' . $order['id'] . '</strong> a été livrée le <b>' . $date_affiche . '</b>.</p>
                <p style="font-size:16px;line-height:1.6;margin:0 0 18px 0;">Nous espérons sincèrement qu\'elle vous plaît et qu\'elle répond à toutes vos attentes.</p>
                <div style="background:#f9f9f9;padding:18px 0;border-radius:8px;margin-bottom:18px;text-align:center;">
                    <span style="font-size:17px;color:#0d6efd;font-weight:bold;">Si vous avez la moindre remarque ou souhaitez faire une réclamation,<br>n\'hésitez pas à <a href="http://localhost/stylish/FO/add_reclamation.php" style="color:#27ae60;text-decoration:underline;">nous contacter ici</a>.</span>
                </div>
                <p style="font-size:15px;line-height:1.5;margin:0 0 18px 0;">Merci pour votre confiance et votre fidélité.<br>L\'équipe Stylish reste à votre écoute.</p>
            </td>
        </tr>
        <tr>
            <td bgcolor="#f4f4f4" style="padding:30px 30px;text-align:center;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                <p style="margin:0;color:#888;font-size:12px;">&copy; ' . date('Y') . ' Stylish Store. Tous droits réservés.</p>
            </td>
        </tr>
    </table>
</body>
</html>';
                $mail->send();
            } catch (Exception $e) {
                // On ignore les erreurs d'envoi pour ne pas bloquer le script
            }
        }
        echo '<p>E-mails envoyés aux clients concernés.</p>';
    }

} catch (Exception $e) {
    echo "<b>Erreur :</b> " . htmlspecialchars($e->getMessage());
} 