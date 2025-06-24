<?php
session_start();
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header('Location: reclamations.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $id_produit = ($type === 'produit' && !empty($_POST['id_produit'])) ? $_POST['id_produit'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO reclamation (id_user, id_produit, type, description, statut) VALUES (?, ?, ?, ?, 'nouveau')");
        $stmt->execute([$user_id, $id_produit, $type, $description]);
        
        // Envoi de l'email de confirmation
        require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
        
        // Récupérer l'email et le prénom de l'utilisateur
        $stmtUser = $pdo->prepare("SELECT email, prenom FROM user WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com'; // À personnaliser
                $mail->SMTPAuth = true;
                $mail->Username = 'youssefcarma@gmail.com'; // À personnaliser
                $mail->Password = 'oupl cahg lkac cxun'; // À personnaliser
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('stylish@gmail.com', 'Stylish');
                $mail->addAddress($user['email'], $user['prenom']);

                $mail->isHTML(true);
                $mail->CharSet = 'UTF-8';
                $mail->Subject = 'Confirmation de votre réclamation - Stylish';
                if ($reclamation) {
                    $typeMail = ucfirst($reclamation['type']);
                    $descriptionMail = nl2br(htmlspecialchars($reclamation['description']));
                    $produitMail = $reclamation['nom_produit'] ? htmlspecialchars($reclamation['nom_produit']) : '-';
                    $dateMail = date('d/m/Y H:i', strtotime($reclamation['date_creation']));
                    $statutMail = ucfirst($reclamation['statut']);
                    $mail->Body = '
                    <div style="font-family: Arial, sans-serif; background: #f8f9fa; padding: 30px;">
                        <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 30px;">
                            <div style="text-align:center; margin-bottom: 20px;">
                                <img src="https://i.ibb.co/vvZBxfg5/logoo.png" alt="Logo Stylish" style="max-width: 180px; height:auto; border-radius: 50%;">
                            </div>
                            <h2 style="color: #2c3e50; text-align: center; margin-bottom: 20px;">Votre réclamation a bien été envoyée</h2>
                            <p style="font-size: 1.1em; color: #333;">Bonjour ' . htmlspecialchars($user['prenom']) . ',</p>
                            <p style="font-size: 1.1em; color: #333;">
                                Nous avons bien reçu votre réclamation. Notre équipe va l\'étudier et vous tiendra informé(e) de l\'avancement de son traitement dans les plus brefs délais.<br><br>
                                <b>Merci de votre confiance.</b>
                            </p>
                            <h3 style="color: #e74c3c; margin-top: 30px;">Détails de votre réclamation :</h3>
                            <table style="width:100%; border-collapse:collapse; margin: 20px 0;">
                                <tr>
                                    <td style="padding:8px; border-bottom:1px solid #eee;"><b>Type :</b></td>
                                    <td style="padding:8px; border-bottom:1px solid #eee;">' . $typeMail . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px; border-bottom:1px solid #eee;"><b>Produit concerné :</b></td>
                                    <td style="padding:8px; border-bottom:1px solid #eee;">' . $produitMail . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px; border-bottom:1px solid #eee;"><b>Description :</b></td>
                                    <td style="padding:8px; border-bottom:1px solid #eee;">' . $descriptionMail . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px; border-bottom:1px solid #eee;"><b>Date :</b></td>
                                    <td style="padding:8px; border-bottom:1px solid #eee;">' . $dateMail . '</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px;"><b>Statut :</b></td>
                                    <td style="padding:8px;">' . $statutMail . '</td>
                                </tr>
                            </table>
                            <div style="margin: 30px 0; text-align: center;">
                                <a href="http://localhost/stylish/FO/reclamations.php" style="background: #e74c3c; color: #fff; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; letter-spacing: 1px;">Voir mes réclamations</a>
                            </div>
                            <p style="font-size: 0.95em; color: #888; text-align: center;">L\'équipe Stylish<br>www.stylish.tn</p>
                        </div>
                    </div>';
                } else {
                    $mail->Body = '
                    <div style="font-family: Arial, sans-serif; background: #f8f9fa; padding: 30px;">
                        <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(44,62,80,0.08); padding: 30px;">
                            <div style="text-align:center; margin-bottom: 20px;">
                                <img src="https://i.ibb.co/vvZBxfg5/logoo.png" alt="Logo Stylish" style="max-width: 180px; height:auto;">
                            </div>
                            <h2 style="color: #2c3e50; text-align: center; margin-bottom: 20px;">Votre réclamation a bien été envoyée</h2>
                            <p style="font-size: 1.1em; color: #333;">Bonjour ' . htmlspecialchars($user['prenom']) . ',</p>
                            <p style="font-size: 1.1em; color: #333;">
                                Nous avons bien reçu votre réclamation. Notre équipe va l\'étudier et vous tiendra informé(e) de l\'avancement de son traitement dans les plus brefs délais.<br><br>
                                <b>Merci de votre confiance.</b>
                            </p>
                            <div style="margin: 30px 0; text-align: center;">
                                <a href="http://localhost/stylish/FO/reclamations.php" style="background: #e74c3c; color: #fff; padding: 12px 30px; border-radius: 25px; text-decoration: none; font-weight: bold; letter-spacing: 1px;">Voir mes réclamations</a>
                            </div>
                            <p style="font-size: 0.95em; color: #888; text-align: center;">L\'équipe Stylish<br>www.stylish.tn</p>
                        </div>
                    </div>';
                }
                $mail->AltBody = "Bonjour " . $user['prenom'] . ",\n\nVotre réclamation a bien été envoyée. Nous allons la traiter dans les plus brefs délais.\n\nL'équipe Stylish";
                $mail->send();
            } catch (Exception $e) {
                // Optionnel : $_SESSION['error_message'] = 'Erreur lors de l\'envoi de l\'email de confirmation.';
            }
        }
        $_SESSION['success_message'] = "Votre réclamation a été enregistrée avec succès.";
    } catch(PDOException $e) {
        $_SESSION['error_message'] = "Une erreur est survenue lors de l'enregistrement de votre réclamation.";
    }
}

header('Location: reclamations.php');
exit();
?> 