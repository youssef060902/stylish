<?php
session_start();
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    
    // Vérifier si l'email existe dans la base de données
    $conn = new mysqli("localhost", "root", "", "stylish");
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Email non trouvé']);
        exit;
    }
    
    // Générer un code de vérification
    $verification_code = rand(100000, 999999);
    
    // Stocker le code dans la session
    $_SESSION['reset_code'] = $verification_code;
    $_SESSION['reset_email'] = $email;
    
    // Envoyer l'email avec le code
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'youssefcarma@gmail.com';
        $mail->Password = 'oupl cahg lkac cxun';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        
        $mail->setFrom('youssefcarma@gmail.com', 'Stylish');
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        $mail->Subject = 'Réinitialisation de votre mot de passe - Stylish';
        $mail->Body = '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #eee; padding: 32px;">
                <img src="/images/main-logo.png" alt="Logo Stylish" style="width: 120px; margin-bottom: 24px;">
                <h2 style="color: #dc3545;">Réinitialisation de votre mot de passe</h2>
                <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
                <p>Votre code de vérification est : <strong style="font-size: 24px; color: #dc3545;">' . $verification_code . '</strong></p>
                <p>Ce code est valable pendant 10 minutes.</p>
                <hr style="margin: 24px 0;">
                <p style="font-size: 14px; color: #888;">Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                <p style="font-size: 14px; color: #888;">L\'équipe Stylish</p>
            </div>
        ';
        
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Code envoyé avec succès']);
    } catch (Exception $e) {
        error_log('Erreur PHPMailer : ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.']);
    }
}
?> 