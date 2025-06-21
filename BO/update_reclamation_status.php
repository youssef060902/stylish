<?php
session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Charger l'autoloader de Composer
require '../vendor/autoload.php';

// Réponse par défaut
$response = ['success' => false, 'message' => 'Une erreur est survenue.'];

// Vérification de l'authentification et des données POST
if (!isset($_SESSION['admin_id'])) {
    $response['message'] = 'Accès non autorisé.';
    echo json_encode($response);
    exit();
}

if (!isset($_POST['id']) || !isset($_POST['statut'])) {
    $response['message'] = 'Données manquantes.';
    echo json_encode($response);
    exit();
}

$id = $_POST['id'];
$statut = $_POST['statut'];
$allowed_statuses = ['nouveau', 'en cours', 'résolu'];

if (!in_array($statut, $allowed_statuses)) {
    $response['message'] = 'Statut non valide.';
    echo json_encode($response);
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

    // 1. Mettre à jour le statut dans la base de données
    $stmt = $pdo->prepare("UPDATE reclamation SET statut = :statut, date_modification = NOW() WHERE id = :id");
    $stmt->execute(['statut' => $statut, 'id' => $id]);

    // 2. Vérifier si la mise à jour a réussi avant de continuer
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Statut mis à jour avec succès.';

        // 3. Si le statut le requiert, préparer et envoyer l'e-mail
        if (in_array($statut, ['en cours', 'résolu'])) {
            $reclamationInfoStmt = $pdo->prepare("
                SELECT
                    u.email, u.prenom,
                    r.type, r.description, r.date_creation,
                    p.nom as product_name
                FROM reclamation r
                JOIN user u ON r.id_user = u.id
                LEFT JOIN produit p ON r.id_produit = p.id
                WHERE r.id = :reclamation_id
            ");
            $reclamationInfoStmt->execute(['reclamation_id' => $id]);
            $reclamationInfo = $reclamationInfoStmt->fetch(PDO::FETCH_ASSOC);

            // 4. S'assurer qu'on a bien récupéré les infos avant d'envoyer
            if ($reclamationInfo) {
                $userName = htmlspecialchars($reclamationInfo['prenom']);
                $reclamationId = $id;
                $logoUrl = 'https://i.ibb.co/vvZBxfg5/logoo.png';

                // -- Variables dynamiques selon le statut --
                $statusColors = [
                    'en cours' => '#ffc107', // Jaune/Orange
                    'résolu'   => '#28a745',   // Vert
                ];
                $statusLabels = [
                    'en cours' => 'En cours de traitement',
                    'résolu'   => 'Résolue',
                ];
                $statusColor = $statusColors[$statut] ?? '#6c757d'; // Gris par défaut
                $statusLabel = $statusLabels[$statut] ?? ucfirst($statut);
                
                $subject = 'Mise à jour de votre réclamation N°' . $reclamationId;
                $title = 'Mise à jour concernant votre réclamation';
                $message = 'Nous vous informons que le statut de votre réclamation N°' . $reclamationId . ' a été mis à jour.';
                $statusBox = '<div style="background-color: ' . $statusColor . '; color: #ffffff; padding: 15px; border-radius: 8px; text-align: center; margin: 20px 0; font-size: 16px;">Statut : <strong>' . $statusLabel . '</strong></div>';

                // -- Création du résumé détaillé de la réclamation --
                $dateCreation = new DateTime($reclamationInfo['date_creation']);
                $formattedDate = $dateCreation->format('d/m/Y à H:i');

                $detailsHtml = '
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin-top: 20px; border: 1px solid #dddddd; border-radius: 8px; overflow: hidden;">
    <tr>
        <td colspan="2" style="padding: 15px; background-color: #f8f9fa; border-bottom: 1px solid #dddddd;">
            <h3 style="margin: 0; font-family: Arial, sans-serif; font-size: 18px; color: #333333;">Résumé de votre réclamation</h3>
        </td>
    </tr>
    <tr>
        <td style="padding: 15px; font-family: Arial, sans-serif; font-size: 16px; background-color: #f8f9fa; border-bottom: 1px solid #dddddd; width: 180px; color: #555555;"><strong>Type :</strong></td>
        <td style="padding: 15px; font-family: Arial, sans-serif; font-size: 16px; border-bottom: 1px solid #dddddd;">' . htmlspecialchars(ucfirst($reclamationInfo['type'])) . '</td>
    </tr>
    <tr>
        <td style="padding: 15px; font-family: Arial, sans-serif; font-size: 16px; background-color: #f8f9fa; border-bottom: 1px solid #dddddd; color: #555555;"><strong>Produit :</strong></td>
        <td style="padding: 15px; font-family: Arial, sans-serif; font-size: 16px; border-bottom: 1px solid #dddddd;">' . htmlspecialchars($reclamationInfo['product_name'] ?: 'Non applicable') . '</td>
    </tr>
    <tr>
        <td style="padding: 15px; font-family: Arial, sans-serif; font-size: 16px; background-color: #f8f9fa; border-bottom: 1px solid #dddddd; color: #555555;"><strong>Date :</strong></td>
        <td style="padding: 15px; font-family: Arial, sans-serif; font-size: 16px; border-bottom: 1px solid #dddddd;">' . $formattedDate . '</td>
    </tr>
    <tr>
        <td style="padding: 15px; font-family: Arial, sans-serif; font-size: 16px; background-color: #f8f9fa; vertical-align: top; color: #555555;"><strong>Description :</strong></td>
        <td style="padding: 15px; font-family: Arial, sans-serif; font-size: 16px; line-height: 1.6;">
            ' . nl2br(htmlspecialchars($reclamationInfo['description'])) . '
        </td>
    </tr>
</table>';

                $htmlBody = '
                    <!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;font-family:Arial,sans-serif;background-color:#f4f4f4;">
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#f4f4f4;"><tr><td align="center">
                    <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color:#ffffff;max-width:600px;margin:20px auto;border-radius:10px;box-shadow:0 0 20px rgba(0,0,0,0.1);">
                        <tr><td align="center" style="padding:30px 20px 20px 20px;"><img src="' . $logoUrl . '" alt="Stylish Logo" style="width:150px;"></td></tr>
                        <tr><td style="padding:20px 40px;">
                            <h1 style="color:#2c3e50;font-size:24px;margin-bottom:20px;">' . $title . '</h1>
                            <p style="color:#555555;font-size:16px;line-height:1.6;">Bonjour ' . $userName . ',</p>
                            <p style="color:#555555;font-size:16px;line-height:1.6;">' . $message . '</p>
                            ' . $statusBox . '
                            ' . $detailsHtml . '
                            <p style="color:#555555;font-size:16px;line-height:1.6;margin-top:30px;">Merci de votre confiance.</p>
                            <p style="color:#555555;font-size:16px;line-height:1.6;">Cordialement,<br>L\'équipe Stylish</p>
                        </td></tr>
                        <tr><td align="center" style="padding:20px;background-color:#2c3e50;color:#dddddd;font-size:12px;border-bottom-left-radius:10px;border-bottom-right-radius:10px;">
                            &copy; ' . date('Y') . ' Stylish. Tous droits réservés.
                        </td></tr>
                    </table>
                    </td></tr></table>
                    </body></html>';

                try {
                    $mail = new PHPMailer(true);
                    // Paramètres du serveur SMTP - À CONFIGURER
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';                     // Spécifiez votre serveur SMTP
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'youssefcarma@gmail.com';            // Votre adresse e-mail SMTP
                    $mail->Password   = 'oupl cahg lkac cxun';     // Mot de passe d'application ou mot de passe SMTP
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->CharSet = 'UTF-8';

                    // Destinataires
                    $mail->setFrom('no-reply@stylish.com', 'Support Stylish');
                    $mail->addAddress($reclamationInfo['email'], $reclamationInfo['prenom']);

                    // Contenu
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body    = $htmlBody;

                    $mail->send();
                    $response['email_status'] = 'E-mail de notification envoyé.';
                } catch (Exception $e) {
                    $response['email_status'] = "L'e-mail n'a pas pu être envoyé. Erreur: {$mail->ErrorInfo}";
                }
            } else {
                 $response['email_status'] = "L'e-mail n'a pas pu être envoyé car les détails de la réclamation n'ont pas été trouvés.";
            }
        }
    } else {
        $response['message'] = 'Aucune modification effectuée ou réclamation non trouvée.';
    }

} catch(PDOException $e) {
    $response['message'] = 'Erreur de base de données : ' . $e->getMessage();
}

echo json_encode($response);
?> 