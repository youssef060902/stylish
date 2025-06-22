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

if (!isset($_POST['id']) || !isset($_POST['date_livraison']) || empty($_POST['date_livraison'])) {
    $response['message'] = 'ID de commande ou date de livraison manquant.';
    echo json_encode($response);
    exit();
}

$order_id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$date_livraison = $_POST['date_livraison'];

// Validation du format (datetime-local => Y-m-d\TH:i)
if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $date_livraison)) {
    $response['message'] = 'Format de date invalide.';
    echo json_encode($response);
    exit();
}

// Conversion en format SQL
$date_sql = str_replace('T', ' ', $date_livraison) . ':00';

try {
    $host = 'localhost';
    $dbname = 'stylish';
    $username = 'root';
    $password = '';
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("UPDATE commande SET date_livraison = :date_livraison WHERE id = :id");
    $stmt->execute(['id' => $order_id, 'date_livraison' => $date_sql]);

    if ($stmt->rowCount() > 0) {
        // Récupérer l'email et le prénom du client
        $stmt_user = $pdo->prepare("SELECT u.prenom, u.email FROM commande c JOIN user u ON c.id_user = u.id WHERE c.id = :id");
        $stmt_user->execute(['id' => $order_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user) {
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
            $mail->addAddress($user['email'], $user['prenom']);
            $mail->isHTML(true);
            $mail->Subject = 'Nouvelle date de livraison pour votre commande Stylish';
            $date_affiche = date('d/m/Y à H\hi', strtotime($date_sql));
            $mail->Body = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Date de livraison modifiée</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background-color:#f4f4f4;color:#333;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse:collapse;margin-top:20px;margin-bottom:20px;background-color:#fff;border-radius:8px;box-shadow:0 4px 8px rgba(0,0,0,0.08);">
        <tr>
            <td align="center" style="padding:40px 0 30px 0;background-color:#000;color:#fff;border-top-left-radius:8px;border-top-right-radius:8px;">
                <img src="https://i.ibb.co/vvZBxfg5/logoo.png" alt="Stylish Logo" width="150" style="display:block;">
            </td>
        </tr>
        <tr>
            <td style="padding:40px 30px;">
                <h1 style="font-size:22px;margin:0 0 18px 0;">Bonjour ' . htmlspecialchars($user['prenom']) . ',</h1>
                <p style="font-size:16px;line-height:1.6;margin:0 0 18px 0;">Nous souhaitons vous informer que la date de livraison prévue pour votre commande <strong>#' . $order_id . '</strong> a été <span style="color:#0d6efd;font-weight:bold;">modifiée</span>.</p>
                <p style="font-size:16px;line-height:1.6;margin:0 0 18px 0;">La nouvelle date de livraison est désormais fixée au&nbsp;:</p>
                <div style="background:#e9f7ef;padding:18px 0;border-radius:8px;margin-bottom:18px;text-align:center;">
                    <span style="font-size:20px;color:#27ae60;font-weight:bold;">' . $date_affiche . '</span>
                </div>
                <p style="font-size:15px;line-height:1.5;margin:0 0 18px 0;">Nous mettons tout en œuvre pour que votre commande vous parvienne dans les meilleures conditions.<br>Merci pour votre confiance et votre fidélité.</p>
                <p style="font-size:15px;line-height:1.5;margin:0;">L\'équipe Stylish reste à votre écoute pour toute question.</p>
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
        }
        $response['success'] = true;
        $response['message'] = 'Date de livraison mise à jour avec succès et notification envoyée au client.';
    } else {
        $response['message'] = 'Aucune modification effectuée (vérifiez l\'ID ou la date).';
    }
} catch (Exception $e) {
    $response['message'] = 'Erreur serveur : ' . $e->getMessage();
}

echo json_encode($response);
exit(); 