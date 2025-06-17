<?php
session_start();
header('Content-Type: application/json');
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
// Connexion à la base de données
$host = "localhost";
$user = "root";
$pass = "";
$db = "stylish";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Erreur de connexion à la base de données"]);
    exit;
}

// Récupération des données POST
$prenom = $_POST['prenom'] ?? '';
$nom = $_POST['nom'] ?? '';
$genre = $_POST['genre'] ?? '';
$date_naissance = $_POST['date_naissance'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$phone = $_POST['phone'] ?? '';
$adresse = $_POST['adresse'] ?? '';
$image = $_FILES['image'] ?? null;

// Validation simple (à compléter selon tes besoins)
if (!$prenom || !$nom || !$genre || !$date_naissance || !$email || !$password || !$phone || !$adresse) {
    echo json_encode(['success' => false, 'message' => "Tous les champs sont obligatoires", 'field' => '']);
    exit;
}

// Validation de l'âge (18 ans minimum)
$birthDate = new DateTime($date_naissance);
$today = new DateTime();
$age = $today->diff($birthDate)->y;

if ($age < 18) {
    echo json_encode(['success' => false, 'message' => "Vous devez avoir au moins 18 ans", 'field' => 'date_naissance']);
    exit;
}

// Validation du mot de passe (minimum 6 caractères)
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => "Le mot de passe doit contenir au moins 6 caractères", 'field' => 'password']);
    exit;
}

// Validation du numéro de téléphone (8 chiffres)
$phone = preg_replace('/\D/', '', $phone);
if (strlen($phone) !== 8) {
    echo json_encode(['success' => false, 'message' => "Le numéro de téléphone doit contenir 8 chiffres", 'field' => 'phone']);
    exit;
}

// Vérification si l'email existe déjà
$check_email_stmt = $conn->prepare("SELECT id FROM user WHERE email = ? LIMIT 1");
$check_email_stmt->bind_param("s", $email);
$check_email_stmt->execute();
$check_email_stmt->store_result();

if ($check_email_stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => "Cet email est déjà utilisé.", 'field' => 'email']);
    $check_email_stmt->close();
    $conn->close();
    exit;
}
$check_email_stmt->close();

// Mot de passe en clair (pas de hashage)
$password_plain = $password;

// Gestion de l'image
$image_path = null;
if ($image && $image['tmp_name'] && $image['size'] > 0) {
    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
    $image_name = uniqid('user_') . '.' . $ext;
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/img/'; // Chemin absolu vers C:/xampp/htdocs/img
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $full_path = $upload_dir . $image_name;
    move_uploaded_file($image['tmp_name'], $full_path);
    // On stocke l'URL complète dans la base
    $image_path = 'http://localhost/img/' . $image_name;
}

// Insertion dans la base
$stmt = $conn->prepare("INSERT INTO user (prenom, nom, genre, date_naissance, age, phone, adresse, email, password, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssisssss", $prenom, $nom, $genre, $date_naissance, $age, $phone, $adresse, $email, $password_plain, $image_path);

if ($stmt->execute()) {
    // Créer la session pour l'utilisateur
    $_SESSION['user_id'] = $stmt->insert_id;
    $_SESSION['user_image'] = $image_path ?: 'http://localhost/img/default.jpg';
    $_SESSION['user_prenom'] = $prenom;
    $_SESSION['user_nom'] = $nom;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_genre'] = $genre;
    $_SESSION['user_date_naissance'] = $date_naissance;
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_adresse'] = $adresse;
    $_SESSION['user_age'] = $age;

    // Envoi de l'email de confirmation avec PHPMailer
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'youssefcarma@gmail.com'; // Votre adresse Gmail
        $mail->Password = 'oupl cahg lkac cxun'; // Votre mot de passe d'application Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('youssefcarma@gmail.com', 'Stylish Shoes');
        $mail->addAddress($email, $prenom . ' ' . $nom);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'Bienvenue sur Stylish - Création de votre compte réussie !';
        $mail->Body = '
            <div style="font-family:Arial,sans-serif;max-width:600px;margin:auto;border:1px solid #eee;padding:32px;">
                <img src="/images/main-logo.png" alt="Logo Stylish" style="width:120px;margin-bottom:24px;">
                <h2 style="color:#dc3545;">Bienvenue, ' . htmlspecialchars($prenom) . ' !</h2>
                <p>Votre compte a bien été créé sur <b>Stylish Shoes</b>.</p>
                <p>Vous pouvez maintenant vous connecter et profiter de nos offres exclusives.</p>
                <hr style="margin:24px 0;">
                <p style="font-size:14px;color:#888;">Si vous n\'êtes pas à l\'origine de cette inscription, ignorez cet email.</p>
                <p style="font-size:14px;color:#888;">L\'équipe Stylish Shoes</p>
            </div>
        ';
        $mail->send();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de l'envoi de l'email : " . $e->getMessage()]);
        exit;
    }
    echo json_encode(['success' => true, 'message' => "Inscription réussie"]);
} else {
    echo json_encode(['success' => false, 'message' => "Erreur lors de l'inscription"]);
}
$stmt->close();
$conn->close();
?>