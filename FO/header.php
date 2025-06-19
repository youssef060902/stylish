<?php
session_start();

// Configuration OAuth2
$client_id = '906846133961-b07e4jjvn870fjpeaalhctrgtu9q2ooc.apps.googleusercontent.com';
$client_secret = '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';
$redirect_uri = 'http://localhost/stylish/FO/index.php';

if (isset($_GET['code'])) {
    // Échanger le code contre un token d'accès
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code' => $_GET['code'],
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ]));
    
    $response = curl_exec($ch);
    $token_data = json_decode($response, true);
    curl_close($ch);
    
    if (isset($token_data['access_token'])) {
        // Récupérer les informations de l'utilisateur
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token_data['access_token']
        ]);
        
        $response = curl_exec($ch);
        $user_info = json_decode($response, true);
        curl_close($ch);
        
        if (isset($user_info['email'])) {
            $email = $user_info['email'];
            $name = $user_info['name'];
            
            // Vérifier si l'utilisateur existe déjà
            $conn = new mysqli("localhost", "root", "", "stylish");
            $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if (!$user) {
                // Générer un mot de passe aléatoire
                $random_password = bin2hex(random_bytes(8));
                
                // Créer un nouvel utilisateur
                $stmt = $conn->prepare("INSERT INTO user (email, nom, prenom, genre, date_naissance, phone, adresse, age, password) VALUES (?, ?, ?, 'non spécifié', '0000-00-00', 'non spécifié', 'non spécifié', 0, ?)");
                $stmt->bind_param("ssss", $email, $name, $name, $random_password);
                $stmt->execute();
                
                // Récupérer l'utilisateur créé
                $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                // Envoyer l'email de bienvenue
                require 'vendor/autoload.php';
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'youssefcarma@gmail.com';
                    $mail->Password = 'oupl cahg lkac cxun';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;
                    $mail->CharSet = 'UTF-8';

                    $mail->setFrom('votre-email@gmail.com', 'Stylish');
                    $mail->addAddress($email, $name);

                    $mail->isHTML(true);
                    $mail->Subject = 'Bienvenue sur Stylish - Votre compte a été créé';
                    $mail->Body = "
                        <h2>Bienvenue sur Stylish !</h2>
                        <p>Votre compte a été créé avec succès.</p>
                        <p>Voici vos identifiants de connexion :</p>
                        <p><strong>Email :</strong> {$email}</p>
                        <p><strong>Mot de passe :</strong> {$random_password}</p>
                        <p>Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe dès votre première connexion.</p>
                        <p>Cordialement,<br>L'équipe Stylish</p>
                    ";

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Erreur d'envoi d'email : " . $mail->ErrorInfo);
                }

                $_SESSION['first_login'] = true;
            }

            // Enregistrer les informations en session
            if (!empty($user['image']) && $user['image'] !== 'default.jpg') {
                $_SESSION['user_image'] = 'http://localhost/' . ltrim($user['image'], '/');
            } else {
                $_SESSION['user_image'] = 'http://localhost/img/default.jpg';
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_prenom'] = $user['prenom'] ?? $name;
            $_SESSION['user_nom'] = $user['nom'] ?? $name;
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_genre'] = $user['genre'] ?? 'non spécifié';
            $_SESSION['user_date_naissance'] = $user['date_naissance'] ?? '0000-00-00';
            $_SESSION['user_phone'] = $user['phone'] ?? 'non spécifié';
            $_SESSION['user_adresse'] = $user['adresse'] ?? 'non spécifié';
            $_SESSION['user_age'] = $user['age'] ?? 0;

            header('Location: index.php');
            exit;
        }
    }
}

// Générer l'URL d'authentification
$auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'prompt' => 'select_account'
]);

if (isset($_SESSION['user_id'])) {
    $user_id_in_session = $_SESSION['user_id'];

    $host = "localhost";
    $user = "root";
    $pass = ""; 
    $db = "stylish"; 

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        
    } else {
        // Préparer et exécuter la requête pour vérifier si l'utilisateur existe
        $check_stmt = $conn->prepare("SELECT id FROM user WHERE id = ? LIMIT 1");
        $check_stmt->bind_param("i", $user_id_in_session);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows === 0) {
            // L'utilisateur n'existe pas en base -> détruire la session
            session_unset();
            session_destroy();
            $_SESSION = array(); // Assurer que $_SESSION est vide immédiatement
            $is_user_in_db = false;
        } else {
            // L'utilisateur existe en base
            $is_user_in_db = true;
        }

        $check_stmt->close();
        $conn->close();
    }

    // Si l'utilisateur n'est pas en base (ou erreur de connexion), on s'assure que la session est vide
    if (!$is_user_in_db && isset($_SESSION['user_id'])) {
         session_unset();
         session_destroy();
         $_SESSION = array();
    }}

    $conn = new mysqli("localhost", "root", "", "stylish");
    $stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        $_SESSION['user_image'] = $user['image'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_genre'] = $user['genre'];}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="camera.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Custom CSS -->
  <style>
    /* Styles généraux */
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #e74c3c;
      --text-color: #2c3e50;
      --light-bg: #f8f9fa;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      --navbar-height: 80px;
    }

    body {
      font-family: 'Poppins', sans-serif;
      padding-top: var(--navbar-height);
    }

    .user-items .shopping-cart,
.user-items .user {
  width: 24px;
  height: 24px;
  display: inline-block;
  vertical-align: middle;
}
.user-items .nav-link,
.user-items a.border-0 {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 40px;
  width: 40px;
  padding: 0;
  margin: 0 2px;
  background: var(--light-bg);
  border-radius: 50%;
}
.user-items .nav-link svg,
.user-items a.border-0 svg {
  width: 24px;
  height: 24px;
  display: block;
  margin: 0 auto;
}
#cart-count {
  position: absolute;
  top: 0;
  right: 0;
  transform: translate(50%, -50%);
  z-index: 2;
  font-size: 0.85rem;
  min-width: 20px;
  min-height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 5px;
}

    /* Styles de la barre de navigation */
    #header-nav {
      background: linear-gradient(to right, #ffffff, #f8f9fa);
      padding: 20px 0;
      transition: var(--transition);
      border-bottom: 1px solid rgba(0,0,0,0.05);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      height: var(--navbar-height);
    }

    #header-nav .container-lg {
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: flex-start;
    }

    #header-nav .navbar-nav {
      gap: 10px;
      margin: 0;
      height: 100%;
      align-items: center;
      /* margin-left: 2rem;  supprimé pour coller le menu au logo */
    }

    #header-nav .navbar-nav .nav-item {
      position: relative;
      height: 100%;
      display: flex;
      align-items: center;
      margin-right: 1.5rem; /* Ajoute un espacement entre les éléments */
    }

    #header-nav .navbar-nav .nav-link {
      color: var(--text-color);
      font-weight: 500;
      padding: 0.5rem 1rem;
      transition: var(--transition);
      position: relative;
    }

    #header-nav .navbar-nav .nav-link:hover {
      color: var(--secondary-color);
    }

    #header-nav .navbar-nav .nav-link::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 50%;
      width: 0;
      height: 2px;
      background-color: var(--secondary-color);
      transition: var(--transition);
      transform: translateX(-50%);
    }

    #header-nav .navbar-nav .nav-link:hover::after {
      width: 100%;
    }

    #header-nav .navbar-brand {
      height: 100%;
      display: flex;
      align-items: center;
    }

    #header-nav .navbar-brand img {
      height: 55px; /* Augmentation de la taille du logo dans la navbar */
      transition: all 0.3s ease;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
    }

    #header-nav .navbar-brand:hover img {
      transform: scale(1.05);
      filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
    }

    

    /* Icônes utilisateur */
    .user-items {
      display: flex;
      align-items: center;
      gap: 15px;
      height: 100%;
      margin-left: auto;
    }

    .user-items .nav-link {
      position: relative;
      padding: 8px;
      border-radius: 50%;
      background: var(--light-bg);
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .user-items .nav-link:hover {
      background: var(--secondary-color);
      transform: translateY(-2px);
    }

    .user-items .nav-link:hover svg {
      color: white;
    }

    .user-items .nav-link svg {
      width: 20px;
      height: 20px;
      transition: var(--transition);
      color: var(--text-color);
    }

    /* Menu mobile */
    @media (max-width: 991px) {
      :root {
        --navbar-height: 70px;
      }

      #header-nav {
        padding: 10px 0;
      }

      #header-nav .offcanvas {
        background: white;
        border-left: 1px solid rgba(0,0,0,0.05);
        top: var(--navbar-height);
        height: calc(100vh - var(--navbar-height));
      }

      #header-nav .navbar-nav {
        padding: 20px 0;
        height: auto;
        flex-direction: column;
        align-items: flex-start;
      }

      #header-nav .navbar-nav .nav-link {
        padding: 12px 20px;
        border-radius: 0;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        height: auto;
      }

      #header-nav .navbar-nav .nav-link:hover {
        background: var(--light-bg);
        padding-left: 25px;
      }

      .user-items {
        padding: 20px;
        border-top: 1px solid rgba(0,0,0,0.05);
        height: auto;
      }
    }

    /* Animation au scroll */
    #header-nav.scrolled {
      padding: 15px 0;
      background: white;
      box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    /* Bouton hamburger */
    .navbar-toggler {
      border: none;
      padding: 8px;
      border-radius: 8px;
      background: var(--light-bg);
      transition: var(--transition);
      margin-left: 15px;
    }

    .navbar-toggler:hover {
      background: var(--secondary-color);
    }

    .navbar-toggler:hover svg {
      color: white;
    }

    .navbar-toggler:focus {
      box-shadow: none;
    }

    .navbar-toggler svg {
      width: 24px;
      height: 24px;
      transition: var(--transition);
      color: var(--text-color);
    }

    /* Style du profil utilisateur */
    .user-profile {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 16px;
      border-radius: 8px;
      transition: var(--transition);
      height: 100%;
    }

    .user-profile:hover {
      background: var(--light-bg);
    }

    .user-profile img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--light-bg);
    }

    .user-profile .user-name {
      font-weight: 500;
      color: var(--text-color);
    }

    /* Ajustement du contenu principal */
    main {
      position: relative;
      z-index: 1;
    }
    .dropdown {
      position: relative;
    }
    .dropdown-menu {
      position: absolute;
      top: 100%; /* Positionne le menu juste en dessous du bouton */
      left: 0;
      z-index: 1000; /* Assure que le menu est au-dessus des autres éléments */
      min-width: 200px; /* Ajustez la largeur minimale si nécessaire */
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* Ajoute une ombre pour un meilleur visuel */
      border: 1px solid rgba(0, 0, 0, 0.04);
      border-radius: 0.5rem;
      padding: 0.5rem 0;
    }
    .dropdown-item {
      padding: 0.75rem 1rem;
    }
    .dropdown-header {
      padding: 0.5rem 1rem;
      font-size: 0.875em;
      color: #6c757d;
      font-weight: 600;
    }
    /* Styles pour la barre de recherche */
    .search-container {
      position: relative;
      width: 100%;
      max-width: 600px;
      margin: 0 auto;
    }

    .search-input {
      width: 100%;
      padding: 12px 20px;
      padding-left: 45px;
      border: 2px solid #e0e0e0;
      border-radius: 25px;
      font-size: 16px;
      transition: all 0.3s ease;
      background: #fff;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .search-input:focus {
      border-color: #007bff;
      box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
      outline: none;
    }

    .search-icon {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #666;
      font-size: 18px;
    }

    /* Style des résultats de recherche */
    .search-results {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      z-index: 1000;
      display: none;
      max-height: 400px;
      overflow-y: auto;
      border: 1px solid #e0e0e0;
      padding: 10px;
    }

    .search-results.active {
      display: block;
      animation: fadeIn 0.2s ease-out;
    }

    .search-result-item {
      padding: 15px;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 15px;
      transition: all 0.2s ease;
      border-radius: 8px;
    }

    .search-result-item:hover {
      background-color: #f8f9fa;
      transform: translateX(5px);
    }

    .search-result-item img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .product-info {
      flex: 1;
    }

    .product-name {
      font-weight: 600;
      color: #333;
      margin-bottom: 5px;
      font-size: 15px;
    }

    .product-price {
      color: #007bff;
      font-weight: 600;
      font-size: 14px;
    }

    /* Animation */
    @keyframes fadeIn {
      from { 
        opacity: 0;
        transform: translateY(-10px);
      }
      to { 
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Style de la barre de défilement */
    .search-results::-webkit-scrollbar {
      width: 6px;
    }

    .search-results::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }

    .search-results::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 3px;
    }

    .search-results::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }

    /* Message "Aucun résultat" */
    .no-results {
      padding: 20px;
      text-align: center;
      color: #666;
      font-size: 14px;
      font-style: italic;
    }

    .btn-google {
      background:rgb(97, 38, 38); /* Fond blanc */
      border: 1px solid #dadce0; /* Bordure Google */
      color: #3c4043; /* Texte gris foncé */
      padding: 10px 15px; /* Ajuster le padding */
      font-weight: 500;
      border-radius: 4px; /* Bords légèrement arrondis */
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px; /* Espace entre icône et texte */
      transition: all 0.2s ease-in-out;
      text-decoration: none !important; /* Enlever le soulignement par défaut */
    }

    .btn-google:hover, .btn-google:focus {
      background: #f8f9fa; /* Léger fond gris au survol/focus */
      box-shadow: 0 1px 3px rgba(0,0,0,0.15), 0 1px 2px rgba(0,0,0,0.08); /* Ombre un peu plus prononcée */
      border-color: #cdd0d4; /* Bordure légèrement plus foncée */
      color: #3C4043 !important; /* S'assurer que le texte reste sombre */
      text-decoration: none; /* Toujours pas de soulignement */
    }

    .google-icon-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
      border-radius: 2px;
      padding: 0;
    }
    .google-icon {
      width: 20px;
      height: 20px;
      display: block;
    }
    .google-btn-text {
        font-size: 0.95rem; /* Taille de police légèrement ajustée */
    }

    .fa-solid,
    .fa-regular,
    .fa-brands {
      font-family: "Font Awesome 6 Free" !important;
      font-weight: 900 !important;
      font-display: block;
    }
    .fa-regular {
      font-weight: 400 !important;
    }

    /* Style de la barre de défilement pour Chrome/Safari */
    .search-results::-webkit-scrollbar {
        width: 8px;
    }

    .search-results::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .search-results::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    .search-results::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Style des catégories */
    .category-tag {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 8px;
        display: inline-block;
    }

    .category-homme {
        background-color: #007bff;
        color: white;
    }

    .category-femme {
        background-color: #ff69b4;
        color: white;
    }

    .category-enfant {
        background-color: #ff8c00;
        color: white;
    }

    /* Style des résultats de recherche */
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        z-index: 1000;
        display: none;
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e0e0e0;
        padding: 10px;
    }

    .search-results.active {
        display: block;
        animation: fadeIn 0.2s ease-out;
    }

    .search-result-item {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.2s ease;
        border-radius: 8px;
    }

    .search-result-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }

    .search-result-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .product-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .product-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
        font-size: 15px;
    }

    .product-price {
        color: #007bff;
        font-weight: 600;
        font-size: 14px;
    }

    /* Style de la barre de défilement */
    .search-results::-webkit-scrollbar {
        width: 6px;
    }

    .search-results::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .search-results::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .search-results::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    /* Message "Aucun résultat" */
    .no-results {
        padding: 20px;
        text-align: center;
        color: #666;
        font-size: 14px;
        font-style: italic;
    }

    /* Style des catégories */
    .category-icon {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }

    .category-homme {
        background-color: #007bff;
    }

    .category-femme {
        background-color: #ff69b4;
    }

    .category-enfant {
        background-color: #ff8c00;
    }

    .product-header {
        display: flex;
        align-items: center;
        margin-bottom: 5px;
    }

    .product-name {
        font-weight: 600;
        color: #333;
        font-size: 15px;
        margin: 0;
    }

    /* Style des résultats de recherche */
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        z-index: 1000;
        display: none;
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e0e0e0;
        padding: 10px;
    }

    .search-results.active {
        display: block;
        animation: fadeIn 0.2s ease-out;
    }

    .search-result-item {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.2s ease;
        border-radius: 8px;
    }

    .search-result-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }

    .search-result-item img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .product-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .product-price {
        color: #007bff;
        font-weight: 600;
        font-size: 14px;
    }

    /* Style des catégories (badge) */
    .category-tag {
        padding: 4px 8px; /* Ajuster le padding pour le rendre compact */
        border-radius: 12px; /* Rendre les coins plus arrondis pour un effet pilule */
        font-size: 11px; /* Rendre la police un peu plus petite */
        font-weight: 600;
        color: white; /* Texte blanc sur fond coloré */
        display: inline-block;
        margin-right: 10px; /* Espace entre le badge et le nom du produit */
        text-transform: uppercase; /* Optionnel: Mettre le texte en majuscules */
    }

    .category-homme {
        background-color: #007bff;
    }

    .category-femme {
        background-color: #ff69b4;
    }

    .category-enfant {
        background-color: #ff8c00;
    }

    .product-header {
        display: flex;
        align-items: center; /* Aligner verticalement l'icône et le nom */
        margin-bottom: 5px;
    }

    .product-name {
        font-weight: 600;
        color: #333;
        font-size: 15px;
        margin: 0;
    }

    /* Styles pour aligner le nom et la catégorie */
    .product-details-line {
        display: flex;
        justify-content: space-between; /* Pour pousser la catégorie à droite */
        align-items: center;
        width: 100%;
    }

    .product-name {
        font-weight: 600;
        color: #333;
        font-size: 15px;
        margin: 0;
    }

    /* Styles pour l'upload d'image */
    .avatar-container {
      position: relative;
      width: 150px;
      height: 150px;
      margin: 0 auto;
      border-radius: 50%;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      border: 3px solid #fff;
    }

    .avatar-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
      display: block;
      transition: all 0.3s ease;
    }

    /* Bloc .avatar-remove-x supprimé - conflits avec .image-remove-x */

    .camera-icon-label {
      position: absolute;
      bottom: 0;
      right: 0;
      width: 45px;
      height: 45px;
      background-color: rgba(0, 0, 0, 0.7);
      border-radius: 50% 0 0 0;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      z-index: 1000;
    }

    .camera-icon-label:hover {
      background-color: rgba(0, 0, 0, 0.9);
    }

    .camera-icon-label i {
      color: white;
      font-size: 20px;
      transition: all 0.3s ease;
    }

    .camera-icon-label:hover i {
      transform: scale(1.1);
    }

    .custom-file-input {
      display: none;
    }

    .camera-upload-text {
      /* Ce style ne sera plus nécessaire */
      /* position: absolute; */
      /* bottom: -25px; */
      /* left: 50%; */
      /* transform: translateX(-50%); */
      /* font-size: 12px; */
      /* color: #6c757d; */
      /* white-space: nowrap; */
      display: none; /* Masquer le texte "Ajouter une photo" */
    }

    /* Styles pour l'image de prévisualisation dans les modales */
    #register-image-preview-img,
    #settings-image-preview-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

    /* Assurez-vous que le wrapper est toujours affiché pour contenir l'image */
    #register-image-preview .avatar-preview-wrapper,
    #settings-image-preview .avatar-preview-wrapper {
      display: flex; /* Toujours afficher le conteneur de prévisualisation */
    }

    /* Masquer le texte camera-upload-text qui n'est plus nécessaire */
    .camera-upload-text {
        display: none;
    }

    /* Corriger les styles conflictuels */
    .avatar-remove-x::before {
      content: '×'; /* Utilise le pseudo-élément pour afficher le 'x' */
      line-height: 1; /* Aligner verticalement le 'x' */
      font-family: Arial, sans-serif; /* Assurer une police standard pour le 'x' */
    }

    /* Supprimer les règles redondantes ou conflictuelles qui cachaient l'icône */
    /* .camera-icon-label, #settings-image-remove-x styles précédemment commentés sont remplacés */

    

    .image-upload-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .image-upload-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 40px;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.3s;
    }

    .image-upload-overlay:hover {
        background: rgba(0, 0, 0, 0.9);
    }

    .image-upload-overlay svg {
        width: 24px;
        height: 24px;
        fill: white;
    }

    .image-upload-container {
    position: relative;
    width: 150px;
    height: 150px;
    margin: 0 auto 20px;
    border-radius: 50%;
    overflow: visible; /* Pour éviter de couper le bouton */
    border: 2px solid #e0e0e0;
    display: block; /* Changement ici : de inline-block à block */
    z-index: 1;
}

.image-remove-x {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: none; /* Contrôlé par JS */
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 1000;
    border: 2px solid #ddd;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.image-remove-x svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: #ff4444;
    display: block; /* Assure que le SVG est rendu */
}

.image-remove-x:hover {
    background: #ff4444;
    border-color: #ff4444;
}

.image-remove-x:hover svg {
    stroke: white;
}

    /* Styles pour le dropdown */
    .dropdown {
      position: relative;
    }
    
    .dropdown-menu {
      display: none;
      position: absolute;
      right: 0;
      min-width: 200px;
      padding: 0.5rem 0;
      margin: 0;
      background-color: #fff;
      border: 1px solid rgba(0,0,0,.15);
      border-radius: 0.25rem;
      box-shadow: 0 0.5rem 1rem rgba(0,0,0,.175);
      z-index: 1000;
    }

    .dropdown-menu.show {
      display: block;
    }

    .dropdown-toggle::after {
      display: inline-block;
      margin-left: 0.255em;
      vertical-align: 0.255em;
      content: "";
      border-top: 0.3em solid;
      border-right: 0.3em solid transparent;
      border-bottom: 0;
      border-left: 0.3em solid transparent;
    }

    .dropdown-item {
      display: block;
      width: 100%;
      padding: 0.5rem 1rem;
      clear: both;
      font-weight: 400;
      color: #212529;
      text-align: inherit;
      white-space: nowrap;
      background-color: transparent;
      border: 0;
      text-decoration: none;
    }

    .dropdown-item:hover {
      color: #16181b;
      text-decoration: none;
      background-color: #f8f9fa;
    }

    .dropdown-divider {
      height: 0;
      margin: 0.5rem 0;
      overflow: hidden;
      border-top: 1px solid #e9ecef;
    }

    .dropdown-header {
      display: block;
      padding: 0.5rem 1rem;
      margin-bottom: 0;
      font-size: 0.875rem;
      color: #6c757d;
      white-space: nowrap;
    }
    /* Nouveau style pour les messages d'erreur */
    .error-message {
        color: #dc3545; /* Rouge pour les erreurs */
        font-size: 0.875em;
        margin-top: 5px;
    }

    .input-group.input-group-sm.mt-1 {
      max-width: 110px !important;
      min-width: 90px;
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
    }
    .input-group.input-group-sm.mt-1 input.input-qty {
      width: 38px !important;
      min-width: 0;
      text-align: center;
      padding: 0 2px;
      font-size: 1rem;
      height: 32px;
    }
    .input-group.input-group-sm.mt-1 button {
      min-width: 28px;
      max-width: 32px;
      padding: 0;
      font-size: 1.1rem;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
  <!-- Font Awesome explicit font-family declaration -->
    
  <!-- Font Awesome CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlcNoz8q4S8y4QzF9d+Pz7x7+L7/v6I/p/4+yL6w1G/3+z+P/2+f/5+y/6+g/0+A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- SweetAlert2 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
  <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    
    <symbol xmlns="http://www.w3.org/2000/svg" id="shopping-cart" viewBox="0 0 24 24" fill="none">
      <path
        d="M21 4H2V6H4.3L7.582 15.025C7.79362 15.6029 8.1773 16.1021 8.68134 16.4552C9.18539 16.8083 9.78556 16.9985 10.401 17H19V15H10.401C9.982 15 9.604 14.735 9.461 14.342L8.973 13H18.246C19.136 13 19.926 12.402 20.169 11.549L21.962 5.275C22.0039 5.12615 22.0109 4.96962 21.9823 4.81763C21.9537 4.66565 21.8904 4.52234 21.7973 4.39889C21.7041 4.27544 21.5837 4.1752 21.4454 4.106C21.3071 4.0368 21.1546 4.00053 21 4ZM18.246 11H8.246L6.428 6H19.675L18.246 11Z"
        fill="black" />
      <path
        d="M10.5 21C11.3284 21 12 20.3284 12 19.5C12 18.6716 11.3284 18 10.5 18C9.67157 18 9 18.6716 9 19.5C9 20.3284 9.67157 21 10.5 21Z"
        fill="black" />
      <path
        d="M16.5 21C17.3284 21 18 20.3284 18 19.5C18 18.6716 17.3284 18 16.5 18C15.6716 18 15 18.6716 15 19.5C15 20.3284 15.6716 21 16.5 21Z"
        fill="black" />
    </symbol>
    
    
    <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
      <path fill="currentColor"
        d="M12 2a5 5 0 1 0 5 5a5 5 0 0 0-5-5zm0 8a3 3 0 1 1 3-3a3 3 0 0 1-3 3zm9 11v-1a7 7 0 0 0-7-7h-4a7 7 0 0 0-7 7v1h2v-1a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v1z" />
    
  </svg>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('showLogin') === 'true') {
        var myModal = new bootstrap.Modal(document.getElementById('modallogin'));
        myModal.show();
        // Supprimer le paramètre de l'URL pour un URL propre après l'affichage du modal
        window.history.replaceState(null, '', window.location.pathname);
      }
    });
  </script>
  <div class="modal fade" id="modallogin" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-fullscreen-md-down modal-md modal-dialog-centered" role="document">
      <div class="modal-content p-4">
        <div class="modal-header mx-auto border-0">
          <h2 class="modal-title fs-3 fw-normal">Login</h2>
        </div>
        <div class="modal-body">
          <div class="login-detail">
            <div class="login-form p-0">
              <div class="col-lg-12 mx-auto">
                <form id="login-form">
                  <input type="text" name="login_email" placeholder="Email " class="mb-3 ps-3 text-input" required>
                  <div class="input-group">
                    <input type="password" id="login_password" name="login_password" placeholder="Password" class="ps-3 text-input" required>
                    <span class="input-group-text border-0 bg-transparent position-absolute end-0" style="z-index: 10;">
                      <button style="border: none !important; background: transparent !important; padding: 0 !important;" type="button" id="toggleLoginPassword">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                          <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238l-.708.709C.272 10.027 0 8.5 0 8s.272-2.027 1.933-2.947l.708.709C1.06 9.72 2 11.5 2 11.5s3-5.5 8-5.5 8 5.5 8 5.5-.939 1.721-2.641 3.238l.708.709C15.728 13.973 16 12.5 16 12.5s-.272 1.473-1.933 2.947l-.708-.709z"/>
                          <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299l.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                          <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884l-12-12 .708-.707 12 12-.708.707z"/>
                        </svg>
                      </button>
                    </span>
                  </div>
                  <div class="checkbox d-flex justify-content-center mt-4">
                    <p class="lost-password text-center">
                      <a href="#" data-bs-toggle="modal" data-bs-target="#modalForgotPassword">Forgot your password?</a>
                    </p>
                  </div>
                  <div class="d-grid gap-2 mt-3">
                    <a href="<?php echo $auth_url; ?>" class="btn btn-google w-100" style="background-color: #FFFFFF; border: 1px solid #DADCE0; border-radius: 4px; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 10px 15px; text-decoration: none !important; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.2s ease-in-out;">
                      <span class="google-icon-wrapper">
                        <img src="images/google.png" alt="Google" class="google-icon">
                      </span>
                      <span class="google-btn-text" style="color: #3C4043; font-weight: 500;">Continuer avec Google</span>
                    </a>
                  </div>
                </form>
              </div>
            </div>
            <div class="modal-footer mt-5 d-flex justify-content-center">
              <button type="button" class="btn btn-red hvr-sweep-to-right dark-sweep" id="login-btn">Login</button>
              <button type="button" class="btn btn-outline-gray hvr-sweep-to-right dark-sweep" data-bs-toggle="modal" data-bs-target="#modalregister">Register</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Login -->

  <!-- Modal Forgot Password -->
  <div class="modal fade" id="modalForgotPassword" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-fullscreen-md-down modal-lg modal-dialog-centered" role="document">
      <div class="modal-content p-4">
        <div class="modal-header mx-auto border-0">
          <h2 class="modal-title fs-3 fw-normal">Réinitialisation du mot de passe</h2>
        </div>
        <div class="modal-body">
          <!-- Étape 1: Email -->
          <div id="forgot-password-step1">
            <form id="forgot-password-form">
              <div class="mb-4">
                <label for="reset_email" class="form-label">Email</label>
                <input type="email" id="reset_email" name="email" class="form-control" required>
              </div>
              <div class="text-center">
                <button type="submit" class="btn btn-red hvr-sweep-to-right dark-sweep">Envoyer le code</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Verification Code -->
  <div class="modal fade" id="modalVerificationCode" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-fullscreen-md-down modal-lg modal-dialog-centered" role="document">
        <div class="modal-content p-4">
            <div class="modal-header mx-auto border-0">
                <h2 class="modal-title fs-3 fw-normal">Vérification du code</h2>
            </div>
            <div class="modal-body">
                <form id="verify-code-form">
                    <div class="mb-4">
                        <label for="verification_code" class="form-label">Code de vérification</label>
                        <input type="text" id="verification_code" name="code" class="form-control" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn btn-red hvr-sweep-to-right dark-sweep">Vérifier le code</button>
                        <button type="button" id="resend-code-btn" class="btn btn-outline-gray hvr-sweep-to-right dark-sweep ms-2" disabled>
                            Renvoyer dans <span id="countdown">60</span>s
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
  <!-- Modal New Password -->
  <div class="modal fade" id="modalNewPassword" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-fullscreen-md-down modal-lg modal-dialog-centered" role="document">
      <div class="modal-content p-4">
        <div class="modal-header mx-auto border-0">
          <h2 class="modal-title fs-3 fw-normal">Nouveau mot de passe</h2>
        </div>
        <div class="modal-body">
          <form id="new-password-form">
            <div class="mb-4">
              <label for="new_password" class="form-label">Nouveau mot de passe</label>
              <div class="input-group">
                <input type="password" id="new_password" name="new_password" class="form-control ps-3 text-input" required>
                <span class="input-group-text border-0 bg-transparent position-absolute end-0" style="z-index: 10;">
                  <button style="border: none !important; background: transparent !important; padding: 0 !important;" type="button" id="toggleNewPassword">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                      <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238l-.708.709C.272 10.027 0 8.5 0 8s.272-2.027 1.933-2.947l.708.709C1.06 9.72 2 11.5 2 11.5s3-5.5 8-5.5 8 5.5 8 5.5-.939 1.721-2.641 3.238l.708.709C15.728 13.973 16 12.5 16 12.5s-.272 1.473-1.933 2.947l-.708-.709z"/>
                      <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299l.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                      <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884l-12-12 .708-.707 12 12-.708.707z"/>
                    </svg>
                  </button>
                </span>
              </div>
            </div>
            <div class="text-center">
              <button type="submit" class="btn btn-red hvr-sweep-to-right dark-sweep">Réinitialiser le mot de passe</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  

   
  

  <!-- Modal Register -->
  <div class="modal fade" id="modalregister" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-fullscreen-md-down modal-lg modal-dialog-centered" role="document">
      <div class="modal-content p-4">
        <div class="modal-header mx-auto border-0">
          <h2 class="modal-title fs-3 fw-normal">Inscription</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="register-detail">
            <div class="register-form p-0">
              <div class="col-lg-12 mx-auto">
                <form id="register-form" class="needs-validation" novalidate enctype="multipart/form-data">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <input type="text" class="form-control ps-3 text-input" name="prenom" placeholder="Prénom *" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <input type="text" class="form-control ps-3 text-input" name="nom" placeholder="Nom *" required>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Genre *</label><br>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="genre" id="homme" value="homme" required>
                      <label class="form-check-label" for="homme">Homme</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="genre" id="femme" value="femme" required>
                      <label class="form-check-label" for="femme">Femme</label>
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Date de naissance *</label>
                    <input type="date" class="form-control ps-3 text-input" name="date_naissance" required>
                  </div>
                  <div class="mb-3">
                    <input type="email" class="form-control ps-3 text-input" name="email" placeholder="Email *" required>
                  </div>
                  <div class="mb-3">
                    <div class="input-group">
                      <input type="password" id="password" class="form-control ps-3 text-input" name="password" placeholder="Mot de passe *" required>
                      <span class="input-group-text border-0 bg-transparent position-absolute end-0" style="z-index: 10;">
                        <button style="border: none !important; background: transparent !important; padding: 0 !important;" type="button" id="togglePassword">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238l-.708.709C.272 10.027 0 8.5 0 8s.272-2.027 1.933-2.947l.708.709C1.06 9.72 2 11.5 2 11.5s3-5.5 8-5.5 8 5.5 8 5.5-.939 1.721-2.641 3.238l.708.709C15.728 13.973 16 12.5 16 12.5s-.272 1.473-1.933 2.947l-.708-.709z"/>
                            <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299l.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                            <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884l-12-12 .708-.707 12 12-.708.707z"/>
                          </svg>
                        </button>
                      </span>
                    </div>
                  </div>
                  <div class="mb-3">
                    <div class="input-group">
                      <input type="password" id="confirm_password" class="form-control ps-3 text-input" name="confirm_password" placeholder="Confirmer le mot de passe *" required>
                      <span class="input-group-text border-0 bg-transparent position-absolute end-0" style="z-index: 10;">
                        <button style="border: none !important; background: transparent !important; padding: 0 !important;" type="button" id="toggleConfirmPassword">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238l-.708.709C.272 10.027 0 8.5 0 8s.272-2.027 1.933-2.947l.708.709C1.06 9.72 2 11.5 2 11.5s3-5.5 8-5.5 8 5.5 8 5.5-.939 1.721-2.641 3.238l.708.709C15.728 13.973 16 12.5 16 12.5s-.272 1.473-1.933 2.947l-.708-.709z"/>
                            <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299l.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                            <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884l-12-12 .708-.707 12 12-.708.707z"/>
                          </svg>
                        </button>
                      </span>
                    </div>
                  </div>
                  <div class="mb-3">
                    <input type="tel" class="form-control ps-3 text-input" name="phone" placeholder="Numéro de téléphone *" required>
                  </div>
                  <div class="mb-3">
                    <input type="text" class="form-control ps-3 text-input" name="adresse" placeholder="Adresse *" required>
                  </div>
                  <div class="mb-4">
  <label class="form-label">Photo de profil</label>
  <div class="image-upload-container">
    <img id="register-image-preview-img" src="http://localhost/img/default.jpg" alt="Photo de profil">
    <div class="image-upload-overlay">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
            <path d="M20 4h-3.17l-1.24-1.35A1.99 1.99 0 0 0 14.12 2H9.88c-.56 0-1.1.24-1.48.65L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 13c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
        </svg>
    </div>
    <div id="register-image-remove-x" class="image-remove-x">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
    </div>
    <input type="file" id="register-image-input" name="profile_image" accept="image/*" style="display: none;">
  </div>
</div>
                  <div class="modal-footer mt-4 d-flex justify-content-center">
                    <button type="submit" class="btn btn-red hvr-sweep-to-right dark-sweep">S'inscrire</button>
                    <button type="button" class="btn btn-outline-gray hvr-sweep-to-right dark-sweep" data-bs-dismiss="modal">Annuler</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Settings -->
<div class="modal fade" id="modalSettings" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-fullscreen-md-down modal-lg modal-dialog-centered" role="document">
        <div class="modal-content p-4">
            <div class="modal-header mx-auto border-0">
                <h2 class="modal-title fs-3 fw-normal">Paramètres</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="settings-form" method="post" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label class="form-label">Photo de profil</label>
                        <div class="image-upload-container">
                            <img id="settings-image-preview-img" src="<?php echo isset($_SESSION['user_image']) && $_SESSION['user_image'] !== 'http://localhost/img/default.jpg' ? htmlspecialchars($_SESSION['user_image']) : 'http://localhost/img/default.jpg'; ?>" alt="Photo de profil">
                            <div class="image-upload-overlay" >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                                    <path d="M20 4h-3.17l-1.24-1.35A1.99 1.99 0 0 0 14.12 2H9.88c-.56 0-1.1.24-1.48.65L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm-8 13c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5z"/>
                                </svg>
                            </div>
                            <span class='image-remove-x' id='settings-image-remove-x'>
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
    </svg>
</span>
                            <input type="file" id="settings-image-input" name="image" accept="image/*" style="display: none;">
                            <input type="hidden" id="delete_image_input" name="delete_image" value="0">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <input type="text" class="form-control ps-3 text-input" name="prenom" placeholder="Prénom *" value="<?php echo htmlspecialchars($_SESSION['user_prenom'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <input type="text" class="form-control ps-3 text-input" name="nom" placeholder="Nom *" value="<?php echo htmlspecialchars($_SESSION['user_nom'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Genre *</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="genre" id="settings-homme" value="homme" <?php echo ($_SESSION['user_genre'] ?? '') === 'homme' ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="settings-homme">Homme</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="genre" id="settings-femme" value="femme" <?php echo ($_SESSION['user_genre'] ?? '') === 'femme' ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="settings-femme">Femme</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date de naissance *</label>
                        <input type="date" class="form-control ps-3 text-input" name="date_naissance" value="<?php echo htmlspecialchars($_SESSION['user_date_naissance'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" class="form-control ps-3 text-input" name="email" placeholder="Email *" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <input type="tel" class="form-control ps-3 text-input" name="phone" placeholder="Numéro de téléphone *" value="<?php echo htmlspecialchars($_SESSION['user_phone'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" class="form-control ps-3 text-input" name="adresse" placeholder="Adresse *" value="<?php echo htmlspecialchars($_SESSION['user_adresse'] ?? ''); ?>" required>
                    </div>
                    <div class="modal-footer mt-4 d-flex justify-content-center">
                        <button type="submit" class="btn btn-red hvr-sweep-to-right dark-sweep">Enregistrer</button>
                        <button type="button" class="btn btn-outline-gray hvr-sweep-to-right dark-sweep" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

  <!-- Modal Change Password -->
  <div class="modal fade" id="modalChangePassword" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-fullscreen-md-down modal-lg modal-dialog-centered" role="document">
      <div class="modal-content p-4">
        <div class="modal-header mx-auto border-0">
          <h2 class="modal-title fs-3 fw-normal">Changer le mot de passe</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="settings-detail">
            <div class="settings-form p-0">
              <div class="col-lg-12 mx-auto">
                <form id="change-password-form" class="needs-validation" novalidate>
                  <div class="mb-4">
                    <label for="current_password" class="form-label">Mot de passe actuel *</label>
                    <div class="input-group">
                      <input type="password" id="current_password" name="current_password" class="form-control ps-3 text-input" placeholder="Mot de passe actuel *" required>
                      <span class="input-group-text border-0 bg-transparent position-absolute end-0" style="z-index: 10;">
                        <button style="border: none !important; background: transparent !important; padding: 0 !important;" type="button" id="toggleCurrentPassword">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238l-.708.709C.272 10.027 0 8.5 0 8s.272-2.027 1.933-2.947l.708.709C1.06 9.72 2 11.5 2 11.5s3-5.5 8-5.5 8 5.5 8 5.5-.939 1.721-2.641 3.238l.708.709C15.728 13.973 16 12.5 16 12.5s-.272 1.473-1.933 2.947l-.708-.709z"/>
                            <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299l.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                            <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884l-12-12 .708-.707 12 12-.708.707z"/>
                          </svg>
                        </button>
                      </span>
                    </div>
                  </div>
                  <div class="mb-4">
                    <label for="new_password_change" class="form-label">Nouveau mot de passe *</label>
                    <div class="input-group">
                      <input type="password" id="new_password_change" name="new_password" class="form-control ps-3 text-input" placeholder="Nouveau mot de passe *" required>
                      <span class="input-group-text border-0 bg-transparent position-absolute end-0" style="z-index: 10;">
                        <button style="border: none !important; background: transparent !important; padding: 0 !important;" type="button" id="toggleNewPasswordChange">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238l-.708.709C.272 10.027 0 8.5 0 8s.272-2.027 1.933-2.947l.708.709C1.06 9.72 2 11.5 2 11.5s3-5.5 8-5.5 8 5.5 8 5.5-.939 1.721-2.641 3.238l.708.709C15.728 13.973 16 12.5 16 12.5s-.272 1.473-1.933 2.947l-.708-.709z"/>
                            <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299l.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                            <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884l-12-12 .708-.707 12 12-.708.707z"/>
                          </svg>
                        </button>
                      </span>
                    </div>
                  </div>
                  <div class="mb-4">
                    <label for="confirm_new_password_change" class="form-label">Confirmer le nouveau mot de passe *</label>
                    <div class="input-group">
                      <input type="password" id="confirm_new_password_change" name="confirm_new_password" class="form-control ps-3 text-input" placeholder="Confirmer le nouveau mot de passe *" required>
                      <span class="input-group-text border-0 bg-transparent position-absolute end-0" style="z-index: 10;">
                        <button style="border: none !important; background: transparent !important; padding: 0 !important;" type="button" id="toggleConfirmNewPasswordChange">
                          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                            <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238l-.708.709C.272 10.027 0 8.5 0 8s.272-2.027 1.933-2.947l.708.709C1.06 9.72 2 11.5 2 11.5s3-5.5 8-5.5 8 5.5 8 5.5-.939 1.721-2.641 3.238l.708.709C15.728 13.973 16 12.5 16 12.5s-.272 1.473-1.933 2.947l-.708-.709z"/>
                            <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299l.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                            <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884l-12-12 .708-.707 12 12-.708.707z"/>
                          </svg>
                        </button>
                      </span>
                    </div>
                  </div>
                  <div class="modal-footer mt-4 d-flex justify-content-center">
                    <button type="submit" class="btn btn-red hvr-sweep-to-right dark-sweep">Changer le mot de passe</button>
                    <button type="button" class="btn btn-outline-gray hvr-sweep-to-right dark-sweep" data-bs-dismiss="modal">Annuler</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <header id="header" class="site-header text-black">
    <nav id="header-nav" class="navbar navbar-expand-lg">
      <div class="container-lg">
        <a class="navbar-brand" href="index.php">
          <div class="logo-navbar-wrapper">
            <img src="images/logoo.png" class="logo" alt="logo">
          </div>
        </a>
        <ul id="navbar" class="navbar-nav fw-bold align-items-center flex-grow-0">
          <li class="nav-item">
            <a class="nav-link me-5" href="nouveautes.php">Nouveautés</a>
          </li>
          <li class="nav-item">
            <a class="nav-link me-5" href="hommes.php">Hommes</a>
          </li>
          <li class="nav-item">
            <a class="nav-link me-5" href="femmes.php">Femmes</a>
          </li>
          <li class="nav-item">
            <a class="nav-link me-5" href="enfants.php">Enfants</a>
          </li>
          <li class="nav-item">
            <a class="nav-link me-5" href="promotions.php">Promotions</a>
          </li>
        </ul>
        <div class="search-container">
          <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #666; font-size: 18px;"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><line x1="21" y1="21" x2="16.65" y2="16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
          <input type="text" id="searchInput" class="search-input" placeholder="Rechercher un produit...">
          <div id="searchResults" class="search-results"></div>
        </div>
        <div class="user-items ps-0 ps-md-5">
          <ul class="d-flex justify-content-end list-unstyled align-item-center m-0">
            <li class="pe-3">
              <?php if(isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                  <button class="btn btn-link dropdown-toggle text-dark text-decoration-none d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php if(!empty($_SESSION['user_image'])): ?>
                      <img src="<?php echo htmlspecialchars($_SESSION['user_image']); ?>" alt="Profil" class="rounded-circle" style="width:32px;height:32px;object-fit:cover;">
                    <?php else: ?>
                      <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-weight:bold;">
                        <?php echo strtoupper(substr($_SESSION['user_prenom'], 0, 1)); ?>
                      </div>
                    <?php endif; ?>
                    <span class="fw-semibold" style="font-size: 0.9rem;">
                      <?php echo htmlspecialchars($_SESSION['user_prenom']); ?>
                    </span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <div class="dropdown-header">Compte</div>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2" href="#" data-bs-toggle="modal" data-bs-target="#modalSettings" data-initialized="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
                          <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
                          <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.256-.52l-.093-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
                        </svg>
                        Paramètres
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2" href="#" data-bs-toggle="modal" data-bs-target="#modalChangePassword">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-key" viewBox="0 0 16 16">
                          <path d="M0 8a4 4 0 0 1 7.465-2H14a.5.5 0 0 1 .354.146l1.5 1.5a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0L13 9.207l-.646.647a.5.5 0 0 1-.708 0L11 9.207l-.646.647a.5.5 0 0 1-.708 0L9 9.207l-.646.647A.5.5 0 0 1 8 10h-.535A4 4 0 0 1 0 8zm4-3a3 3 0 1 0 2.712 4.285A.5.5 0 0 1 7.163 9h.63l.853-.854a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.793-.793-1-1h-6.63a.5.5 0 0 1-.451-.285A3 3 0 0 0 4 5z"/>
                          <path d="M4 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        </svg>
                        Changer mot de passe
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2" href="favorites.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16">
                          <path d="m8 2.748-.717-.737C5.6.281 2.514 3.053 3.824 6.143c.636 1.528 2.293 3.356 4.176 4.857 1.883-1.5 3.54-3.329 4.176-4.857C13.486 3.053 10.4.28 8.717 2.01L8 2.748zm0 8.684C-7.333 3.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171.057-.059.116-.116.176-.171C12.721-3.042 23.333 3.868 8 11.432z"/>
                        </svg>
                        Mes favoris
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2" href="reclamations.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-dots" viewBox="0 0 16 16">
                          <path d="M2 2a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h2.586l2.707 2.707a1 1 0 0 0 1.414 0L11.414 13H14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H2zm0 1h12a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1h-2.586l-2.707 2.707a.5.5 0 0 1-.708 0L5.586 11H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/>
                          <circle cx="5" cy="8" r="1"/>
                          <circle cx="8" cy="8" r="1"/>
                          <circle cx="11" cy="8" r="1"/>
                        </svg>
                        Mes réclamations
                      </a>
                    </li>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-header">Actions</div>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="#" data-bs-toggle="modal" data-bs-target="#modalDeleteAccount">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                          <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                          <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                        </svg>
                        Supprimer le compte
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="logout.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                          <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                          <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                        </svg>
                        Déconnexion
                      </a>
                    </li>
                  </ul>
                </div>
              <?php else: ?>
                <a href="#" data-bs-toggle="modal" data-bs-target="#modallogin" class="border-0">
                  <svg class="user" width="24" height="24">
                    <use xlink:href="#user"></use>
                  </svg>
                </a>
              <?php endif; ?>
            </li>
            <li class="pe-3">
              <a href="#" id="cartModalToggle" class="border-0 position-relative">
                <svg class="shopping-cart" width="24" height="24">
                  <use xlink:href="#shopping-cart"></use>
                </svg>
                <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.8rem;z-index:1001;display:none;">0</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </header>
  <style>
    /* Import Playfair Display font */
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap');

    /* Police principale pour tout le site */
    * {
      font-family: "Times New Roman", Times, serif !important;
    }
    .navbar-brand img {
      height: 55px;
      width: 55px;
      object-fit: contain;
      border-radius: 50%;
      background: transparent;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: none;
      border: none;
    }
    .logo-navbar-wrapper {
      background: #fff;
      border-radius: 50%;
      box-shadow: 0 4px 16px rgba(44,62,80,0.10), 0 1.5px 6px rgba(231,76,60,0.10);
      border: 2px solid #e74c3c;
      padding: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: box-shadow 0.3s, border-color 0.3s, transform 0.3s;
      height: 67px;
      width: 67px;
    }
    .navbar-brand:hover .logo-navbar-wrapper {
      box-shadow: 0 8px 24px rgba(231,76,60,0.18), 0 2px 8px rgba(44,62,80,0.10);
      border-color: #2c3e50;
      transform: scale(1.07);
    }
    @media (max-width: 991px) {
      .logo-navbar-wrapper {
        height: 48px;
        width: 48px;
        padding: 3px;
      }
      .navbar-brand img {
        height: 38px;
        width: 38px;
      }
    }
    </style>
    <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/plugins.js"></script>
  <script src="js/script.js"></script>
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const registerForm = document.getElementById('register-form');
      
      registerForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Récupération des champs
        const prenom = registerForm.querySelector('input[name="prenom"]');
        const nom = registerForm.querySelector('input[name="nom"]');
        const genre = registerForm.querySelector('input[name="genre"]:checked');
        const dateNaissance = registerForm.querySelector('input[name="date_naissance"]');
        const email = registerForm.querySelector('input[name="email"]');
        const password = registerForm.querySelector('input[name="password"]');
        const confirmPassword = registerForm.querySelector('input[name="confirm_password"]');
        const telephone = registerForm.querySelector('input[name="phone"]');
        const adresse = registerForm.querySelector('input[name="adresse"]');
        const conditions = registerForm.querySelector('input[type="checkbox"]');
        
        // Validation des champs
        if (!prenom.value.trim()) {
          showError('Le prénom est requis');
          prenom.focus();
          return;
        }
        
        if (!nom.value.trim()) {
          showError('Le nom est requis');
          nom.focus();
          return;
        }
        
        if (!genre) {
          showError('Le genre est requis');
          return;
        }
        
        if (!dateNaissance.value) {
          showError('La date de naissance est requise');
          dateNaissance.focus();
          return;
        }
        
        if (!email.value.trim()) {
          showError('L\'email est requis');
          email.focus();
          return;
        }
        
        if (!isValidEmail(email.value)) {
          showError('Veuillez entrer une adresse email valide');
          email.focus();
          return;
        }
        
        if (!password.value) {
          showError('Le mot de passe est requis');
          password.focus();
          return;
        }
        
        if (password.value.length < 6) {
          showError('Le mot de passe doit contenir au moins 6 caractères');
          password.focus();
          return;
        }
        
        if (password.value !== confirmPassword.value) {
          showError('Les mots de passe ne correspondent pas');
          confirmPassword.focus();
          return;
        }
        
        if (!telephone.value.trim()) {
          showError('Le numéro de téléphone est requis');
          telephone.focus();
          return;
        }
        
        
        
        if (!adresse.value.trim()) {
          showError('L\'adresse est requise');
          adresse.focus();
          return;
        }
        
        
        
        // Si tout est valide, on envoie en AJAX
        var formData = new FormData(registerForm);

        fetch('register.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            Swal.fire({
              title: 'Inscription réussie!',
              text: data.message,
              icon: 'success',
              confirmButtonText: 'OK',
              confirmButtonColor: '#dc3545'
            }).then(() => {
              registerForm.reset();
              $('#modalregister').modal('hide');
              location.reload(); // Recharge la page actuelle au lieu de rediriger vers index.php
            });
          } else {
            Swal.fire({
              title: 'Erreur!',
              text: data.message,
              icon: 'error',
              confirmButtonText: 'OK',
              confirmButtonColor: '#dc3545'
            });
          }
        })
        .catch(() => {
          Swal.fire({
            title: 'Erreur!',
            text: 'Erreur lors de la communication avec le serveur.',
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#dc3545'
          });
        });
      });
      
      // Fonction pour afficher les erreurs
      function showError(message) {
        Swal.fire({
          title: 'Erreur!',
          text: message,
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#dc3545'
        });
      }
      
      // Validation email
      function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
      }
      
      // Validation téléphone
      function isValidPhone(phone) {
        const re = /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
        return re.test(phone);
      }

      // Fonction pour basculer la visibilité du mot de passe
      function togglePasswordVisibility(buttonId, inputId) {
        const button = document.getElementById(buttonId);
        const input = document.getElementById(inputId);
        
        if (button && input) {
          button.addEventListener('click', function() {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            // Changer l'icône
            const icon = button.querySelector('svg');
            if (type === 'text') { // Si le type est maintenant text (mot de passe visible)
              // Afficher l'icône d'œil fermé
              icon.innerHTML = `
                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
              `;
              
            } else { // Si le type est maintenant password (mot de passe caché)
              // Afficher l'icône d'œil ouvert
              icon.innerHTML = `
                <path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5S0 8 0 8s.939 1.721 2.641 3.238l-.708.709C.272 10.027 0 8.5 0 8s.272-2.027 1.933-2.947l.708.709C1.06 9.72 2 11.5 2 11.5s3-5.5 8-5.5 8 5.5 8 5.5-.939 1.721-2.641 3.238l.708.709C15.728 13.973 16 12.5 16 12.5s-.272 1.473-1.933 2.947l-.708-.709z"/>
                <path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829l.822.822zm-2.943 1.299l.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829z"/>
                <path d="M3.35 5.47c-.18.16-.353.322-.518.487A13.134 13.134 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7.029 7.029 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884l-12-12 .708-.707 12 12-.708.707z"/>
              `;
            }
          });
        }
      }

      // Initialiser les boutons de basculement
      togglePasswordVisibility('togglePassword', 'password');
      togglePasswordVisibility('toggleConfirmPassword', 'confirm_password');
      togglePasswordVisibility('toggleLoginPassword', 'login_password');
      togglePasswordVisibility('toggleCurrentPassword', 'current_password');
      togglePasswordVisibility('toggleNewPasswordChange', 'new_password_change');
      togglePasswordVisibility('toggleConfirmNewPasswordChange', 'confirm_new_password_change');
      togglePasswordVisibility('toggleNewPassword', 'new_password');
      

      // Gestion du formulaire de paramètres
      const settingsForm = document.getElementById('settings-form');
      if (settingsForm) {
        const inputs = settingsForm.querySelectorAll('input');
        
        // Fonction pour afficher un message d'erreur dans un pop-up
        function showErrorPopup(message) {
            Swal.fire({
                title: 'Erreur!',
                text: message,
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        }
        
        // Validation de l'âge (18 ans minimum)
        function validateAge(dateString) {
            const birthDate = new Date(dateString);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                return age - 1;
            }
            return age;
        }
        
        // Validation du numéro de téléphone (8 chiffres)
        function validatePhone(phone) {
            return /^\d{8}$/.test(phone.replace(/\D/g, ''));
        };
        
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = true;
            
            // Validation de tous les champs
            inputs.forEach(input => {
                if (input.required && !input.value.trim()) {
                    showErrorPopup('Le champ ' + input.name + ' est requis');
                    isValid = false;
                }
                
                if (input.name === 'date_naissance') {
                    const age = validateAge(input.value);
                    if (age < 18) {
                        showErrorPopup('Vous devez avoir au moins 18 ans');
                        isValid = false;
                    }
                }
                
                if (input.name === 'phone') {
                    if (!validatePhone(input.value)) {
                        showErrorPopup('Le numéro de téléphone doit contenir 8 chiffres');
                        isValid = false;
                    }
                }
            });
            
            if (isValid) {
                const formData = new FormData(this);
                
                fetch('update_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Succès!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#dc3545'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        showErrorPopup(data.message);
                    }
                })
                .catch(() => {
                    showErrorPopup('Erreur lors de la communication avec le serveur.');
                });
            }
        });
      }

      // Gestion de la suppression de l'image de profil
      const deleteProfileImageBtn = document.getElementById('deleteProfileImage');
      if (deleteProfileImageBtn) {
        console.log('Bouton de suppression trouvé.'); // Debug: Bouton trouvé
        deleteProfileImageBtn.addEventListener('click', function() {
          console.log('Bouton de suppression cliqué.'); // Debug: Clic détecté
          Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible !",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
          }).then((result) => {
            if (result.isConfirmed) {
              fetch('delete_profile_image.php')
                .then(response => response.json())
                .then(data => {
                  if (data.success) {
                    Swal.fire({
                      title: 'Succès!',
                      text: data.message,
                      icon: 'success',
                      confirmButtonText: 'OK',
                      confirmButtonColor: '#dc3545'
                    }).then(() => {
                      // Suppression réussie, mettre à jour l'interface sans pop-up
                      const settingsForm = document.getElementById('settings-form');
                      const imagePreviewDiv = settingsForm.querySelector('.mb-3 img');
                      if (imagePreviewDiv) {
                        imagePreviewDiv.closest('.mb-3').remove();
                      }
                      const deleteButtonDiv = document.getElementById('deleteProfileImage');
                      if (deleteButtonDiv) {
                        deleteButtonDiv.closest('.mt-2').style.display = 'none';
                      }
                      const profileImageNav = document.querySelector('#header img[alt="Profil"]');
                      const userInitialsNav = document.querySelector('#header div[style*="background-color:#dc3545"]');
                      if (profileImageNav) profileImageNav.style.display = 'none';
                      if (userInitialsNav) userInitialsNav.style.display = 'flex';
                    });
                  } else {
                    // Gérer l'erreur avec un pop-up
                    Swal.fire({
                      title: 'Erreur!',
                      text: data.message,
                      icon: 'error',
                      confirmButtonText: 'OK',
                      confirmButtonColor: '#dc3545'
                    });
                  }
                })
                .catch(() => {
                  Swal.fire({
                    title: 'Erreur!',
                    text: 'Erreur lors de la communication avec le serveur.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                  });
                });
            }
          });
        });
      }

      // Gestion du formulaire de changement de mot de passe
      const changePasswordForm = document.getElementById('change-password-form');
      const modalChangePassword = document.getElementById('modalChangePassword');

      if (changePasswordForm && modalChangePassword) {
        // Réinitialiser le formulaire quand la modale est cachée
        modalChangePassword.addEventListener('hidden.bs.modal', function () {
            changePasswordForm.reset();
            // Supprimer les classes d'erreur et les messages d'erreur
            changePasswordForm.querySelectorAll('.text-input').forEach(input => {
                input.classList.remove('error');
            });
            changePasswordForm.querySelectorAll('.error-message').forEach(msg => msg.remove());
        });

        // Validation dynamique lors de la saisie
        const inputs = changePasswordForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                // Supprimer l'erreur immédiatement lors de la saisie
                this.classList.remove('error');
                // Cibler le conteneur parent (.mb-4) pour trouver le message d'erreur
                const parentContainer = this.closest('.mb-4');
                const errorMessage = parentContainer ? parentContainer.querySelector('.error-message') : null;

                if (errorMessage) {
                    errorMessage.remove();
                }

                // Si c'est le champ de confirmation, vérifier la correspondance en temps réel
                if (this.id === 'confirm_new_password_change') {
                    const newPassword = document.getElementById('new_password_change');
                    // Supprimer l'ancien message de non-correspondance s'il existe
                    const mismatchErrorMessage = this.closest('.mb-4').querySelector('.error-message');
                    if (mismatchErrorMessage && mismatchErrorMessage.textContent === 'Les mots de passe ne correspondent pas') {
                         mismatchErrorMessage.remove();
                    }

                    if (this.value && newPassword.value && this.value !== newPassword.value) {
                        this.classList.add('error');
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = 'Les mots de passe ne correspondent pas';
                        this.closest('.mb-4').appendChild(errorMessage);
                    }
                }
                 // Si c'est le champ du nouveau mot de passe, vérifier la longueur minimale
                if (this.id === 'new_password_change') {
                    // Supprimer l'ancien message de longueur minimale s'il existe
                    const lengthErrorMessage = this.closest('.mb-4').querySelector('.error-message');
                     if (lengthErrorMessage && lengthErrorMessage.textContent.includes('caractères')) {
                         lengthErrorMessage.remove();
                    }

                    if (this.value && this.value.length < 6) {
                         this.classList.add('error');
                         const errorMessage = document.createElement('div');
                         errorMessage.className = 'error-message';
                         errorMessage.textContent = 'Le mot de passe doit contenir au moins 6 caractères';
                         this.closest('.mb-4').appendChild(errorMessage);
                    }
                     // Vérifier également la correspondance si le champ de confirmation est rempli
                     const confirmPasswordInput = document.getElementById('confirm_new_password_change');
                     if(confirmPasswordInput && confirmPasswordInput.value){
                         const mismatchErrorMessage = confirmPasswordInput.closest('.mb-4').querySelector('.error-message');
                         if (mismatchErrorMessage && mismatchErrorMessage.textContent === 'Les mots de passe ne correspondent pas') {
                              mismatchErrorMessage.remove();
                         }
                          if(confirmPasswordInput.value !== this.value){
                              confirmPasswordInput.classList.add('error');
                              const errorMessage = document.createElement('div');
                              errorMessage.className = 'error-message';
                              errorMessage.textContent = 'Les mots de passe ne correspondent pas';
                              confirmPasswordInput.closest('.mb-4').appendChild(errorMessage);
                          } else {
                               confirmPasswordInput.classList.remove('error');
                          }
                     }
                }
            });
        });

        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const currentPassword = document.getElementById('current_password');
            const newPassword = document.getElementById('new_password_change');
            const confirmNewPassword = document.getElementById('confirm_new_password_change');
            let isValid = true;

            // Supprimer tous les messages d'erreur existants avant de valider à nouveau
            changePasswordForm.querySelectorAll('.error-message').forEach(msg => msg.remove());
            changePasswordForm.querySelectorAll('.text-input').forEach(input => input.classList.remove('error'));


            // Validation du mot de passe actuel
            if (!currentPassword.value.trim()) {
                currentPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Veuillez remplir ce champ';
                currentPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            }

            // Validation du nouveau mot de passe
            if (!newPassword.value.trim()) {
                newPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Veuillez remplir ce champ';
                newPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            } else if (newPassword.value.length < 6) {
                newPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Le mot de passe doit contenir au moins 6 caractères';
                newPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            }

            // Validation de la confirmation du mot de passe
            if (!confirmNewPassword.value.trim()) {
                confirmNewPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Veuillez remplir ce champ';
                confirmNewPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            } else if (confirmNewPassword.value !== newPassword.value) {
                confirmNewPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Les mots de passe ne correspondent pas';
                confirmNewPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            const formData = new FormData(this);

            fetch('change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Succès!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    }).then(() => {
                        window.location.href = 'logout.php?showLogin=true'; // Redirige vers logout avec paramètre
                    });
                } else {
                     // Supprimer les messages d'erreur précédents et ajouter les nouveaux du serveur
                     changePasswordForm.querySelectorAll('.error-message').forEach(msg => msg.remove());
                     changePasswordForm.querySelectorAll('.text-input').forEach(input => input.classList.remove('error'));

                     // Afficher l'erreur spécifique du serveur si elle concerne un champ (par exemple, mot de passe actuel incorrect)
                     if (data.message.includes('actuel est incorrect')) {
                         const currentPasswordInput = document.getElementById('current_password');
                         if (currentPasswordInput) {
                              currentPasswordInput.classList.add('error');
                              const errorMessage = document.createElement('div');
                              errorMessage.className = 'error-message';
                              errorMessage.textContent = data.message;
                              currentPasswordInput.closest('.mb-4').appendChild(errorMessage);
                         } else {
                             // Afficher l'erreur générale si le champ n'est pas trouvé
                             Swal.fire({
                                 title: 'Erreur!',
                                 text: data.message,
                                 icon: 'error',
                                 confirmButtonText: 'OK',
                                 confirmButtonColor: '#dc3545'
                             });
                         }
                     } else {
                         // Afficher les autres erreurs générales du serveur
                         Swal.fire({
                             title: 'Erreur!',
                             text: data.message,
                             icon: 'error',
                             confirmButtonText: 'OK',
                             confirmButtonColor: '#dc3545'
                         });
                     }
                }
            })
            .catch(() => {
                Swal.fire({
                    title: 'Erreur!',
                    text: 'Erreur lors de la communication avec le serveur.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            });
        });
      }

      // Gestion du bouton Supprimer le compte
      const deleteAccountBtn = document.getElementById('delete-account-btn');
      if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', function(e) {
          e.preventDefault(); // Empêche le comportement par défaut du lien #
          Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette action est irréversible ! Votre compte sera définitivement supprimé.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer mon compte!',
            cancelButtonText: 'Annuler'
          }).then((result) => {
            if (result.isConfirmed) {
              // L'utilisateur a confirmé, envoyer la requête de suppression
              fetch('delete_account.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json' // ou 'application/x-www-form-urlencoded' si tu utilises $_POST
                },
                body: JSON.stringify({ user_id: <?php echo $_SESSION['user_id'] ?? 'null'; ?> })
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  Swal.fire(
                    'Supprimé!',
                    data.message,
                    'success'
                  ).then(() => {
                    window.location.href = 'logout.php'; // Rediriger vers la page d'accueil ou de déconnexion
                  });
                } else {
                  Swal.fire(
                    'Erreur!',
                    data.message,
                    'error'
                  );
                }
              })
              .catch(() => {
                Swal.fire(
                  'Erreur!',
                  'Erreur lors de la communication avec le serveur.',
                  'error'
                );
              });
            }
          });
        });
      }
    });

    // Supposons que tu as un formulaire avec id="login-form"
    document.getElementById('login-form').addEventListener('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);

        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                // Ferme la modal et recharge la page pour afficher la photo de profil
                $('#modallogin').modal('hide');
                location.reload(); // <-- Rafraîchit la page
            } else {
                Swal.fire({
                    title: 'Erreur!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            Swal.fire({
                title: 'Erreur!',
                text: 'Erreur lors de la communication avec le serveur.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        });
    });

    // Gestion du login AJAX
    document.getElementById('login-btn').addEventListener('click', function(e) {
        e.preventDefault();
        var loginForm = document.getElementById('login-form');
        var emailInput = loginForm.querySelector('input[name="login_email"]');
        var passwordInput = loginForm.querySelector('input[name="login_password"]');
        var isValid = true;

        // Réinitialiser les styles d'erreur
        emailInput.classList.remove('error');
        passwordInput.classList.remove('error');
        
        // Supprimer les messages d'erreur existants
        const existingErrorMessages = document.querySelectorAll('.error-message');
        existingErrorMessages.forEach(msg => msg.remove());

        // Validation de l'email
        if (!emailInput.value.trim()) {
            emailInput.classList.add('error');
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            errorMessage.textContent = 'Veuillez remplir ce champ';
            emailInput.parentNode.insertBefore(errorMessage, emailInput.nextSibling);
            isValid = false;
        }

        // Validation du mot de passe
        if (!passwordInput.value.trim()) {
            passwordInput.classList.add('error');
            const errorMessage = document.createElement('div');
            errorMessage.className = 'error-message';
            errorMessage.textContent = 'Veuillez remplir ce champ';
            passwordInput.parentNode.insertBefore(errorMessage, passwordInput.nextSibling);
            isValid = false;
        }

        if (!isValid) {
            return;
        }

        var formData = new FormData(loginForm);
        // Adapter les noms pour l'envoi à login.php
        var email = formData.get('login_email');
        var password = formData.get('login_password');
        var data = new FormData();
        data.append('login_email', email);
        data.append('login_password', password);
        fetch('login.php', {
            method: 'POST',
            body: data
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                $('#modallogin').modal('hide');
                location.reload();
            } else {
                Swal.fire({
                    title: 'Erreur!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            Swal.fire({
                title: 'Erreur!',
                text: 'Erreur lors de la communication avec le serveur.',
                icon: 'error',
                confirmButtonText: 'OK',
                confirmButtonColor: '#dc3545'
            });
        });
    });

    // Effacer les erreurs dynamiquement lors de la saisie dans le formulaire de connexion
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        const inputs = loginForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                // Supprimer l'erreur immédiatement lors de la saisie
                this.classList.remove('error');
                // Cibler le conteneur parent pour trouver le message d'erreur
                const parentContainer = this.closest('.mb-3') || this.closest('.input-group');
                const errorMessage = parentContainer ? parentContainer.parentNode.querySelector('.error-message') : null;

                if (errorMessage) {
                    errorMessage.remove();
                }
            });
        });
    }

    // Effacer les erreurs dynamiquement lors de la saisie dans le formulaire de changement de mot de passe
    const changePasswordForm = document.getElementById('change-password-form');
    const modalChangePassword = document.getElementById('modalChangePassword');

    if (changePasswordForm && modalChangePassword) {
        // Réinitialiser le formulaire quand la modale est cachée
        modalChangePassword.addEventListener('hidden.bs.modal', function () {
            changePasswordForm.reset();
            // Supprimer les classes d'erreur et les messages d'erreur
            changePasswordForm.querySelectorAll('.text-input').forEach(input => {
                input.classList.remove('error');
            });
            changePasswordForm.querySelectorAll('.error-message').forEach(msg => msg.remove());
        });

        // Validation dynamique lors de la saisie
        const inputs = changePasswordForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                // Supprimer l'erreur immédiatement lors de la saisie
                this.classList.remove('error');
                // Cibler le conteneur parent (.mb-4) pour trouver le message d'erreur
                const parentContainer = this.closest('.mb-4');
                const errorMessage = parentContainer ? parentContainer.querySelector('.error-message') : null;

                if (errorMessage) {
                    errorMessage.remove();
                }

                // Si c'est le champ de confirmation, vérifier la correspondance en temps réel
                if (this.id === 'confirm_new_password_change') {
                    const newPassword = document.getElementById('new_password_change');
                    // Supprimer l'ancien message de non-correspondance s'il existe
                    const mismatchErrorMessage = this.closest('.mb-4').querySelector('.error-message');
                    if (mismatchErrorMessage && mismatchErrorMessage.textContent === 'Les mots de passe ne correspondent pas') {
                         mismatchErrorMessage.remove();
                    }

                    if (this.value && newPassword.value && this.value !== newPassword.value) {
                        this.classList.add('error');
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = 'Les mots de passe ne correspondent pas';
                        this.closest('.mb-4').appendChild(errorMessage);
                    }
                }
                 // Si c'est le champ du nouveau mot de passe, vérifier la longueur minimale
                if (this.id === 'new_password_change') {
                    // Supprimer l'ancien message de longueur minimale s'il existe
                    const lengthErrorMessage = this.closest('.mb-4').querySelector('.error-message');
                     if (lengthErrorMessage && lengthErrorMessage.textContent.includes('caractères')) {
                         lengthErrorMessage.remove();
                    }

                    if (this.value && this.value.length < 6) {
                         this.classList.add('error');
                         const errorMessage = document.createElement('div');
                         errorMessage.className = 'error-message';
                         errorMessage.textContent = 'Le mot de passe doit contenir au moins 6 caractères';
                         this.closest('.mb-4').appendChild(errorMessage);
                    }
                     // Vérifier également la correspondance si le champ de confirmation est rempli
                     const confirmPasswordInput = document.getElementById('confirm_new_password_change');
                     if(confirmPasswordInput && confirmPasswordInput.value){
                         const mismatchErrorMessage = confirmPasswordInput.closest('.mb-4').querySelector('.error-message');
                         if (mismatchErrorMessage && mismatchErrorMessage.textContent === 'Les mots de passe ne correspondent pas') {
                              mismatchErrorMessage.remove();
                         }
                          if(confirmPasswordInput.value !== this.value){
                              confirmPasswordInput.classList.add('error');
                              const errorMessage = document.createElement('div');
                              errorMessage.className = 'error-message';
                              errorMessage.textContent = 'Les mots de passe ne correspondent pas';
                              confirmPasswordInput.closest('.mb-4').appendChild(errorMessage);
                          } else {
                               confirmPasswordInput.classList.remove('error');
                          }
                     }
                }
            });
        });

        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const currentPassword = document.getElementById('current_password');
            const newPassword = document.getElementById('new_password_change');
            const confirmNewPassword = document.getElementById('confirm_new_password_change');
            let isValid = true;

            // Supprimer tous les messages d'erreur existants avant de valider à nouveau
            changePasswordForm.querySelectorAll('.error-message').forEach(msg => msg.remove());
            changePasswordForm.querySelectorAll('.text-input').forEach(input => input.classList.remove('error'));


            // Validation du mot de passe actuel
            if (!currentPassword.value.trim()) {
                currentPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Veuillez remplir ce champ';
                currentPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            }

            // Validation du nouveau mot de passe
            if (!newPassword.value.trim()) {
                newPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Veuillez remplir ce champ';
                newPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            } else if (newPassword.value.length < 6) {
                newPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Le mot de passe doit contenir au moins 6 caractères';
                newPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            }

            // Validation de la confirmation du mot de passe
            if (!confirmNewPassword.value.trim()) {
                confirmNewPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Veuillez remplir ce champ';
                confirmNewPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            } else if (confirmNewPassword.value !== newPassword.value) {
                confirmNewPassword.classList.add('error');
                const errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                errorMessage.textContent = 'Les mots de passe ne correspondent pas';
                confirmNewPassword.closest('.mb-4').appendChild(errorMessage);
                isValid = false;
            }

            if (!isValid) {
                return;
            }

            const formData = new FormData(this);

            fetch('change_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Succès!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#dc3545'
                    }).then(() => {
                        window.location.href = 'logout.php?showLogin=true'; // Redirige vers logout avec paramètre
                    });
                } else {
                     // Supprimer les messages d'erreur précédents et ajouter les nouveaux du serveur
                     changePasswordForm.querySelectorAll('.error-message').forEach(msg => msg.remove());
                     changePasswordForm.querySelectorAll('.text-input').forEach(input => input.classList.remove('error'));

                     // Afficher l'erreur spécifique du serveur si elle concerne un champ (par exemple, mot de passe actuel incorrect)
                     if (data.message.includes('actuel est incorrect')) {
                         const currentPasswordInput = document.getElementById('current_password');
                         if (currentPasswordInput) {
                              currentPasswordInput.classList.add('error');
                              const errorMessage = document.createElement('div');
                              errorMessage.className = 'error-message';
                              errorMessage.textContent = data.message;
                              currentPasswordInput.closest('.mb-4').appendChild(errorMessage);
                         } else {
                             // Afficher l'erreur générale si le champ n'est pas trouvé
                             Swal.fire({
                                 title: 'Erreur!',
                                 text: data.message,
                                 icon: 'error',
                                 confirmButtonText: 'OK',
                                 confirmButtonColor: '#dc3545'
                             });
                         }
                     } else {
                         // Afficher les autres erreurs générales du serveur
                         Swal.fire({
                             title: 'Erreur!',
                             text: data.message,
                             icon: 'error',
                             confirmButtonText: 'OK',
                             confirmButtonColor: '#dc3545'
                         });
                     }
                }
            })
            .catch(() => {
                Swal.fire({
                    title: 'Erreur!',
                    text: 'Erreur lors de la communication avec le serveur.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#dc3545'
                });
            });
        });
      }

      // Réinitialiser le formulaire de connexion lorsque le modal se ferme
      $('#modallogin').on('hidden.bs.modal', function () {
          const loginForm = document.getElementById('login-form');
          loginForm.reset();
          
          // Supprimer les classes d'erreur
          const inputs = loginForm.querySelectorAll('.text-input');
          inputs.forEach(input => {
              input.classList.remove('error');
          });
          
          // Supprimer les messages d'erreur
          const errorMessages = loginForm.querySelectorAll('.error-message');
          errorMessages.forEach(msg => msg.remove());
      });

      

      // === START Refactored Image Upload Logic ===

      // La logique précédente a été refactorisée pour être plus simple et directe.
      // Le bloc commenté ci-dessous a été remplacé.

      // Central function to manage image preview and camera icon visibility
      // `fileInput`: the actual input type="file" element
      // `cameraLabel`: the <label> element for the camera icon
      // `previewParentContainer`: the container where the image preview will be injected (e.g., #register-image-preview or #settings-image-upload-wrapper)
      /*
      function updateImageUploadUI(fileInput, cameraLabel, previewParentContainer) {
        if (fileInput.files && fileInput.files[0]) {
          const reader = new FileReader();
          reader.onload = function(e) {
            // Hide camera label
            if (cameraLabel) cameraLabel.style.display = 'none';

            // Remove any existing preview to prevent duplicates
            const existingPreview = previewParentContainer.querySelector('.avatar-preview-wrapper');
            if (existingPreview) {
              existingPreview.remove();
            }

            // Create and append the new preview
            const newPreviewHtml = `
              <span class='avatar-preview-wrapper'>
                <img src="${e.target.result}" alt="Prévisualisation" class="avatar-preview">
                <span class='avatar-remove-x' id='${fileInput.id.replace('input', 'remove-x')}'>&times;</span>
                <label class='camera-icon' for='${fileInput.id}'>
                  <i class="fas fa-camera"></i>
                </label>
              </span>
            `;
            previewParentContainer.insertAdjacentHTML('afterbegin', newPreviewHtml); // Add at the beginning

            // Attach event listener for the dynamically created 'x' button
            const xBtn = document.getElementById(fileInput.id.replace('input', 'remove-x'));
            if (xBtn) {
              xBtn.addEventListener('click', function() {
                fileInput.value = ''; // Clear the file input
                // If this is the settings form, mark for deletion
                if (fileInput.id === 'settings-image-input') {
                  const deleteInput = document.getElementById('delete_image_input');
                  if (deleteInput) deleteInput.value = '1';
                }
                updateImageUploadUI(fileInput, cameraLabel, previewParentContainer); // Reset UI
              });
            }

            // If a new image is selected, ensure delete_image_input is '0'
            if (fileInput.id === 'settings-image-input') {
              const deleteInput = document.getElementById('delete_image_input');
              if (deleteInput) deleteInput.value = '0';
            }
          };
          reader.readAsDataURL(fileInput.files[0]);
        } else {
          // No file selected or cleared: show camera label and clear preview
          if (cameraLabel) cameraLabel.style.display = 'flex';
          const existingPreview = previewParentContainer.querySelector('.avatar-preview-wrapper');
          if (existingPreview) {
            existingPreview.remove();
          }

          // If settings form and no file selected, and no current image (i.e. 'x' was clicked on existing image)
          if (fileInput.id === 'settings-image-input') {
            const deleteInput = document.getElementById('delete_image_input');
            if (deleteInput) deleteInput.value = '1'; // Mark for deletion
          }
        }
      }

      // --- Register Modal Image Handling ---
      const registerImageInput = document.getElementById('register-image-input');
      const registerCameraLabel = document.getElementById('register-camera-label');
      const registerImageUploadWrapper = document.querySelector('#modalregister .camera-upload-wrapper'); // Direct parent for register preview

      if (registerImageInput && registerCameraLabel && registerImageUploadWrapper) {
        // Initial state for register form: camera icon visible, preview hidden (PHP handles initial rendering)
        // We still need to ensure the label is visible if no file is selected.
        if (!registerImageInput.files[0]) {
            registerCameraLabel.style.display = 'flex';
        }

        registerImageInput.addEventListener('change', function() {
          updateImageUploadUI(this, registerCameraLabel, registerImageUploadWrapper);
        });

        // Ensure camera label triggers input click
        registerCameraLabel.addEventListener('click', function(e) {
          e.preventDefault();
          registerImageInput.click();
        });
      }

      // --- Settings Modal Image Handling ---
      const settingsImageInput = document.getElementById('settings-image-input');
      const settingsCameraLabel = document.getElementById('settings-camera-label'); // This will only exist if no initial image
      const settingsImageUploadWrapper = document.getElementById('settings-image-upload-wrapper'); // Main wrapper for settings image
      const settingsDeleteImageInput = document.getElementById('delete_image_input');

      if (settingsImageInput && settingsImageUploadWrapper) {
        // Initial setup when modal opens or page loads (PHP handles initial rendering)
        // If an image exists initially, ensure camera label is hidden.
        const initialImagePreview = settingsImageUploadWrapper.querySelector('.avatar-preview-wrapper');
        if (initialImagePreview && settingsCameraLabel) {
          settingsCameraLabel.style.display = 'none';
        } else if (settingsCameraLabel) { // If no initial image preview, show camera label
          settingsCameraLabel.style.display = 'flex';
        }

        // Add event listener for file input change
        settingsImageInput.addEventListener('change', function() {
          updateImageUploadUI(this, settingsCameraLabel, settingsImageUploadWrapper);
        });

        // Add event listener for existing 'x' button (if image loaded by PHP) or dynamically created 'x' (event delegation)
        settingsImageUploadWrapper.addEventListener('click', function(event) {
          if (event.target && event.target.classList.contains('avatar-remove-x')) {
            // Only handle if it's the specific 'x' button
            const targetXBtn = event.target;
            const fileInput = settingsImageInput;
            const cameraLabel = settingsCameraLabel;
            const previewParentContainer = settingsImageUploadWrapper;

            // Perform UI update via the central function
            fileInput.value = ''; // Clear the file input
            if (settingsDeleteImageInput) settingsDeleteImageInput.value = '1'; // Mark for deletion
            updateImageUploadUI(fileInput, cameraLabel, previewParentContainer); // Reset UI
          }
        });

        // Ensure camera label triggers input click for settings modal (if it exists)
        if (settingsCameraLabel) {
          settingsCameraLabel.addEventListener('click', function(e) {
            e.preventDefault();
            settingsImageInput.click();
          });
        }

        // Handle modal show event to ensure correct initial state of controls
        $('#modalSettings').on('show.bs.modal', function () {
            // Re-evaluate the state of the image preview and camera icon based on session image
            // This is important because PHP renders a snapshot, and JS needs to align
            const currentImageSrc = '<?php echo isset($_SESSION['user_image']) && $_SESSION['user_image'] !== 'http://localhost/img/default.jpg' ? htmlspecialchars($_SESSION['user_image']) : ''; ?>';

            const existingPreview = settingsImageUploadWrapper.querySelector('.avatar-preview-wrapper');
            if (currentImageSrc) {
              // If there should be an image from session, display it and hide camera label
              if (!existingPreview) { // Only create if not already there by PHP
                settingsImageUploadWrapper.insertAdjacentHTML('afterbegin', `
                  <span class='avatar-preview-wrapper'>
                    <img src="${currentImageSrc}" alt="Photo de profil actuelle" class="avatar-preview">
                    <span class='avatar-remove-x' id='settings-image-remove-x'>&times;</span>
                  </span>
                `);
                // Re-attach listener for the new x-button in the modal after its opened.
                const xBtn = document.getElementById('settings-image-remove-x');
                if (xBtn) {
                  xBtn.addEventListener('click', function() {
                    settingsImageInput.value = '';
                    if (settingsDeleteImageInput) settingsDeleteImageInput.value = '1';
                    updateImageUploadUI(settingsImageInput, settingsCameraLabel, settingsImageUploadWrapper);
                  });
                }
              }
              if (settingsCameraLabel) settingsCameraLabel.style.display = 'none';
              if (settingsDeleteImageInput) settingsDeleteImageInput.value = '0'; // Not deleting initially
            } else {
              // No image in session, ensure camera label is visible and no preview
              if (existingPreview) existingExistingPreview.remove();
              if (settingsCameraLabel) settingsCameraLabel.style.display = 'flex';
              if (settingsDeleteImageInput) settingsDeleteImageInput.value = '0'; // No image to delete
            }
        });
      }
      */
      // === END Refactored Image Upload Logic ===

      // Removals of old, conflicting JS
      // Removed global vars like `imageDeleted`, `currentUserImage`, `originalUserImage`
      // Removed `handleImagePreview` function and its old specific calls
      // Removed older direct event listeners for `registerImageInput` and `settingsImageInput` change, replaced by `updateImageUploadUI`
      // Removed specific `DOMContentLoaded` and `$(document).ready` blocks related to old image handling, including `addSettingsImageRemoveHandler`.
      
    </script>
    <script src="js/forgot-password.js"></script>
    <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour configurer l'upload d'image
    function setupImageUpload(fileInputId, previewImgId, removeXId, deleteHiddenInputId = null) {
        const fileInput = document.getElementById(fileInputId);
        const previewImg = document.getElementById(previewImgId);
        const removeXBtn = document.getElementById(removeXId);
        const deleteHiddenInput = deleteHiddenInputId ? document.getElementById(deleteHiddenInputId) : null;
        const defaultImagePath = 'http://localhost/img/default.jpg';

        if (!fileInput || !previewImg || !removeXBtn) {
            console.error(`[${fileInputId}] Missing required elements for image upload setup.`);
            return;
        }

        // Mettre à jour la visibilité du bouton "X"
        function updateXButtonVisibility(currentPreviewSrc) {
      const srcToCheck = currentPreviewSrc || previewImg.currentSrc; // Utiliser la source passée ou currentSrc
      console.log(`[${fileInputId}] Entering updateXButtonVisibility - srcToCheck: ${srcToCheck}, defaultPath: ${defaultImagePath}`);
      if (srcToCheck && !srcToCheck.includes(defaultImagePath) && srcToCheck.trim().length > 0) {
          console.log(`[${fileInputId}] Showing removeXBtn - srcToCheck: ${srcToCheck}`);
          removeXBtn.style.display = 'flex';
          removeXBtn.style.opacity = '1';
          removeXBtn.style.visibility = 'visible';
          removeXBtn.style.zIndex = '1000';
          removeXBtn.style.transform = 'translateZ(0)';
      } else {
          console.log(`[${fileInputId}] Hiding removeXBtn - srcToCheck: ${srcToCheck}`);
          removeXBtn.style.display = 'none';
          removeXBtn.style.opacity = '0';
          removeXBtn.style.visibility = 'hidden';
      }
  }
  
          // Gérer le changement d'image
          fileInput.addEventListener('change', function() {
              if (this.files && this.files[0]) {
                  const reader = new FileReader();
                  reader.onload = function(e) {
                      previewImg.src = e.target.result;
                      if (deleteHiddenInput) {
                          deleteHiddenInput.value = '0'; // Nouvelle image uploadée
                      }
                      updateXButtonVisibility(e.target.result); // Passer la nouvelle source directement
                  };
                  reader.readAsDataURL(this.files[0]);
              } else {
                  // No file selected or cleared
                  previewImg.src = defaultImagePath;
                  if (deleteHiddenInput) {
                      deleteHiddenInput.value = '1'; // Marquer pour suppression
                  }
                  updateXButtonVisibility(defaultImagePath); // Passer l'image par défaut
              }
          });
  
          // Gérer le clic sur le bouton "X"
          removeXBtn.addEventListener('click', function(e) {
              e.preventDefault();
              fileInput.value = ''; // Vider l'input
              previewImg.src = defaultImagePath; // Revenir à l'image par défaut
              if (deleteHiddenInput) {
                  deleteHiddenInput.value = '1'; // Marquer pour suppression
              }
              updateXButtonVisibility(defaultImagePath); // Passer l'image par défaut
          });
  
          // Gérer le clic sur l'overlay pour déclencher l'input
          const overlay = previewImg.closest('.image-upload-container')?.querySelector('.image-upload-overlay');
          if (overlay) {
              overlay.addEventListener('click', function() {
                  fileInput.click();
              });
          }
  
          // Initialiser la visibilité du bouton "X" au chargement
          // Utiliser previewImg.src pour l'état initial, qui est défini par PHP
          if (previewImg.complete) {
            updateXButtonVisibility(previewImg.src); // Passer la source initiale
          } else {
            previewImg.onload = () => updateXButtonVisibility(previewImg.src); // Passer la source quand l'image est chargée
          }
      }
  
      // Configuration pour le modal "Register"
      setupImageUpload('register-image-input', 'register-image-preview-img', 'register-image-remove-x');
  
      // Configuration pour le modal "Settings"
      setupImageUpload('settings-image-input', 'settings-image-preview-img', 'settings-image-remove-x', 'delete_image_input');
  });
  </script>

  <!-- Modal de suppression de compte -->
  <div class="modal fade" id="modalDeleteAccount" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title">Supprimer le compte</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="text-center mb-4">
            <div class="delete-account-icon mb-3">
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#dc3545" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995z"/>
              </svg>
            </div>
            <h4 class="mb-3">Êtes-vous sûr de vouloir supprimer votre compte ?</h4>
            <p class="text-muted">Cette action est irréversible. Toutes vos données seront définitivement supprimées.</p>
          </div>
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-danger" id="confirmDelete">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash me-2" viewBox="0 0 16 16">
                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
              </svg>
              Supprimer définitivement
            </button>
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
  document.getElementById('confirmDelete').addEventListener('click', function() {
    Swal.fire({
      title: 'Êtes-vous sûr ?',
      text: "Cette action est irréversible !",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Oui, supprimer mon compte',
      cancelButtonText: 'Annuler'
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('delete_account.php')
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                title: 'Compte supprimé !',
                text: 'Votre compte a été supprimé avec succès.',
                icon: 'success',
                confirmButtonColor: '#3085d6'
              }).then(() => {
                if (data.redirect) {
                  window.location.href = data.url;
                }
              });
            } else {
              Swal.fire({
                title: 'Erreur !',
                text: data.message,
                icon: 'error',
                confirmButtonColor: '#3085d6'
              });
            }
          })
          .catch(error => {
            console.error('Erreur:', error);
            Swal.fire({
              title: 'Erreur !',
              text: 'Une erreur est survenue lors de la suppression du compte.',
              icon: 'error',
              confirmButtonColor: '#3085d6'
            });
          });
      }
    });
  });
  </script>

  <!-- Bootstrap Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Popper.js -->
  
  
    <script>
      // ... existing code ...
      // Fonction de recherche dynamique
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');

        function getCategoryClass(category) {
            category = category.toLowerCase();
            if (category.includes('homme')) return 'category-homme';
            if (category.includes('femme')) return 'category-femme';
            if (category.includes('enfant')) return 'category-enfant';
            return 'category-homme'; // Par défaut
        }

        function displayProducts(products) {
            if (products.length > 0) {
                searchResults.innerHTML = products.map(product => `
                    <div class="search-result-item" onclick="window.location.href='product-details.php?id=${product.id}'">
                        <img src="${product.image}" alt="${product.name}">
                        <div class="product-info">
                            <div class="product-details-line">
                                <div class="product-name">${product.name}</div>
                                <span class="category-tag ${getCategoryClass(product.category)}">${product.category}</span>
                            </div>
                            <div class="product-price">${product.price} DT</div>
                        </div>
                    </div>
                `).join('');
            } else {
                searchResults.innerHTML = '<div class="no-results">Aucun résultat trouvé</div>';
            }
            searchResults.style.display = 'block';
            searchResults.classList.add('active');
        }

        function loadProducts(query = '') {
            fetch(`search_products.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    displayProducts(data);
                })
                .catch(error => {
                    console.error('Erreur lors de la recherche:', error);
                    searchResults.innerHTML = '<div class="no-results">Erreur lors de la recherche</div>';
                    searchResults.style.display = 'block';
                    searchResults.classList.add('active');
                });
        }

        // Charger tous les produits au démarrage
        // loadProducts();

        // Afficher les produits quand on clique sur la barre de recherche
        searchInput.addEventListener('focus', function() {
            loadProducts(this.value.trim());
        });

        // Gérer la recherche en temps réel
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            loadProducts(query);
        });

        // Ajouter un gestionnaire d'événements à l'icône de recherche
        const searchIcon = document.querySelector('.search-icon');
        if (searchIcon) {
            searchIcon.addEventListener('click', function() {
                searchInput.focus();
            });
        }

        // Fermer les résultats quand on clique en dehors
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
                searchResults.classList.remove('active');
            }
        });
      });
  // ... existing code ...
  </script>
  <script>
// Fonction centrale pour gérer l'interface de téléchargement d'image

  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
  // Fonction pour initialiser les dropdowns
  function initializeDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown-toggle:not(.dropdown-initialized)');
    console.log('Dropdowns found:', dropdowns.length, dropdowns);
    dropdowns.forEach(dropdown => {
      dropdown.addEventListener('click', function(e) {
        e.preventDefault();
        const dropdownMenu = this.nextElementSibling;
        dropdownMenu.classList.toggle('show');
      });
      dropdown.classList.add('dropdown-initialized');
    });
  }

  // Initialiser au chargement
  initializeDropdowns();

  // Fermer le dropdown quand on clique en dehors
  document.addEventListener('click', function(e) {
    if (!e.target.matches('.dropdown-toggle')) {
      const dropdowns = document.querySelectorAll('.dropdown-menu.show');
      dropdowns.forEach(dropdown => {
        dropdown.classList.remove('show');
      });
    }
  });

  // Observer les changements dynamiques dans le DOM
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.addedNodes.length) {
        initializeDropdowns();
      }
    });
  });
  observer.observe(document.body, { childList: true, subtree: true });
});
  </script>
  
  <script>
document.addEventListener('DOMContentLoaded', function() {
  const settingsLink = document.querySelector('[data-bs-target="#modalSettings"]');
  
  settingsLink.addEventListener('click', function(e) {
    if (this.getAttribute('data-initialized') === 'false') {
      const modal = document.querySelector('#modalSettings');
      const imageInput = modal.querySelector('input[type="file"]');
      const imagePreview = modal.querySelector('#image-preview');
      
      if (imageInput && imagePreview) {
        imageInput.addEventListener('change', function() {
          if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
              imagePreview.src = e.target.result;
            };
            reader.readAsDataURL(this.files[0]);
          }
        });
      }
      
      this.setAttribute('data-initialized', 'true');
    }
  });
});
</script>
<script>
$(function() {
  // Supprimer le backdrop à la fermeture du modal d'inscription
  $('#modalregister').on('hidden.bs.modal', function () {
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('body').css('padding-right', '');
  });
  // Supprimer le backdrop à la fermeture du modal mot de passe oublié
  $('#modalForgotPassword').on('hidden.bs.modal', function () {
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('body').css('padding-right', '');
  });
  // Supprimer le backdrop à la fermeture du modal de connexion
  $('#modallogin').on('hidden.bs.modal', function () {
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open');
    $('body').css('padding-right', '');
  });
});
</script>
<script>
// ... existing code ...
// Gestion du mini-panier
function renderCartDropdown() {
  let cart = JSON.parse(localStorage.getItem('cart')) || [];
  let cartItemsList = document.getElementById('cart-items-list');
  let total = 0;
  if (cart.length === 0) {
    cartItemsList.innerHTML = '<div class="text-center text-muted">Votre panier est vide.</div>';
  } else {
    cartItemsList.innerHTML = cart.map(item => `
      <div class="d-flex align-items-center mb-2">
        <img src="${item.image}" alt="${item.nom}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;margin-right:10px;">
        <div class="flex-grow-1">
          <div class="fw-semibold">${item.nom}</div>
          <div class="small text-muted">Pointure : ${item.pointure} | Qté : ${item.quantite}</div>
        </div>
        <div class="fw-bold ms-2">${(item.prix * item.quantite).toFixed(2)} DT</div>
      </div>
    `).join('');
  }
  // Mettre à jour le total
  cart.forEach(item => { total += item.prix * item.quantite; });
  let cartTotalEl = document.getElementById('cart-total');
  if (cartTotalEl) {
    cartTotalEl.textContent = total.toFixed(2) + ' DT';
  }
}
// Affichage/fermeture du dropdown panier
const cartToggle = document.getElementById('cartDropdownToggle');
const cartDropdown = document.getElementById('cartDropdown');
if (cartToggle && cartDropdown) {
  cartToggle.addEventListener('click', function(e) {
    e.preventDefault();
    renderCartDropdown();
    cartDropdown.style.display = cartDropdown.style.display === 'block' ? 'none' : 'block';
  });
  // Fermer le dropdown si on clique en dehors
  document.addEventListener('click', function(e) {
    if (!cartDropdown.contains(e.target) && !cartToggle.contains(e.target)) {
      cartDropdown.style.display = 'none';
    }
  });
}
// Mettre à jour le badge et le total au chargement
function updateCartCountHeader() {
    if (typeof isUserLoggedIn !== 'undefined' && !isUserLoggedIn) {
        let cartCountEl = document.getElementById('cart-count');
        if (cartCountEl) cartCountEl.style.display = 'none';
        return;
    }
    let cart = window.sessionCart || [];
    let count = 0;
    cart.forEach(item => { count += parseInt(item.quantite); });
    let cartCountEls = document.querySelectorAll('#cart-count');
    console.log('[DEBUG] updateCartCountHeader - sessionCart:', cart, 'count:', count, 'nb #cart-count:', cartCountEls.length);
    cartCountEls.forEach(cartCountEl => {
        cartCountEl.textContent = count;
        cartCountEl.style.display = count > 0 ? 'inline-block' : 'none';
        console.log('[DEBUG] Badge text now:', cartCountEl.textContent);
    });
}
function updateCartTotalHeader() {
  let cart = JSON.parse(localStorage.getItem('cart')) || [];
  let total = 0;
  cart.forEach(item => { total += item.prix * item.quantite; });
  let cartTotalEl = document.getElementById('cart-total');
  if (cartTotalEl) {
    cartTotalEl.textContent = total.toFixed(2) + ' DT';
  }
}
document.addEventListener('DOMContentLoaded', function() {
  updateCartCountHeader();
  updateCartTotalHeader();
});
// Permettre la mise à jour du header depuis d'autres pages
window.updateCartCount = updateCartCountHeader;
window.updateCartTotal = updateCartTotalHeader;
</script>

<!-- Modal Panier -->
<div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:18px;overflow:hidden;">
      <div class="modal-header" style="background:#fff;">
        <h5 class="modal-title fw-bold" id="cartModalLabel" style="color:#e74c3c;">Mon panier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
      </div>
      <div class="modal-body p-4" style="background:#fff;">
        <div id="cart-items-list-modal" style="max-height:220px;overflow-y:auto;"></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
          <span class="fw-bold">Total :</span>
          <span id="cart-total-modal" class="fw-bold">0.00 DT</span>
        </div>
      </div>
      <div class="modal-footer bg-white">
        <a href="checkout.php" class="btn btn-primary w-100" style="background:#e74c3c;border:none;border-radius:8px;font-weight:600;">Commander</a>
      </div>
    </div>
  </div>
</div>

<script>
// ... existing code ...
// Gestion du popup panier (modale)
function renderCartModal() {
    let cart = window.sessionCart || [];
    let cartItemsList = document.getElementById('cart-items-list-modal');
    let total = 0;
    if (cart.length === 0) {
        cartItemsList.innerHTML = '<div class="text-center text-muted">Votre panier est vide.</div>';
    } else {
        cartItemsList.innerHTML = cart.map(item => `
            <div class="d-flex align-items-center mb-2">
                <img src="${item.image}" alt="${item.nom}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;margin-right:10px;">
                <div class="flex-grow-1">
                    <div class="fw-semibold">${item.nom}</div>
                    <div class="small text-muted">Pointure : ${item.pointure}</div>
                    <input type="number" class="form-control form-control-sm input-qty mt-1" min="1" value="${item.quantite}" style="width:60px;max-width:100%;display:inline-block;" onchange="updateCartQuantity(${item.id_produit}, ${item.id_pointure}, this.value)">
                </div>
                <div class="fw-bold ms-2" style="min-width:70px;">${(item.prix_final * item.quantite).toFixed(2)} DT</div>
                <button class="btn btn-link text-danger btn-remove-item ms-2 p-0" title="Supprimer" onclick="removeCartItem(${item.id_produit}, ${item.id_pointure})">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                </button>
            </div>
        `).join('');
    }
    cart.forEach(item => { total += (item.prix_final || item.prix) * item.quantite; });
    let cartTotalEl = document.getElementById('cart-total-modal');
    if (cartTotalEl) {
        cartTotalEl.textContent = total.toFixed(2) + ' DT';
    }
}

function updateCartQuantity(id_produit, id_pointure, quantite) {
    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'update',
            id: id_produit,
            pointure: id_pointure,
            quantite: quantite
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.sessionCart = data.cart;
            renderCartModal();
            updateCartCountHeader();
        }
    });
}

function removeCartItem(id_produit, id_pointure) {
    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'remove',
            id: id_produit,
            pointure: id_pointure
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.sessionCart = data.cart;
            renderCartModal();
            updateCartCountHeader();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateCartFromServer();
});
// ... existing code ...
</script>

<script>
const cartModalToggle = document.getElementById('cartModalToggle');
if (cartModalToggle) {
  cartModalToggle.addEventListener('click', function(e) {
    e.preventDefault();
    if (typeof isUserLoggedIn !== 'undefined' && !isUserLoggedIn) {
      var loginModal = new bootstrap.Modal(document.getElementById('modallogin'));
      loginModal.show();
      return;
    }
    renderCartModal();
    var modal = new bootstrap.Modal(document.getElementById('cartModal'));
    modal.show();
  });
}

function updateCartCountHeader() {
    if (typeof isUserLoggedIn !== 'undefined' && !isUserLoggedIn) {
        let cartCountEl = document.getElementById('cart-count');
        if (cartCountEl) cartCountEl.style.display = 'none';
        return;
    }
    let cart = window.sessionCart || [];
    let count = 0;
    cart.forEach(item => { count += parseInt(item.quantite); });
    let cartCountEl = document.getElementById('cart-count');
    if (cartCountEl) {
        cartCountEl.textContent = count;
        cartCountEl.style.display = 'inline-block';
    }
}
// ... existing code ...


// ... existing code ...
// Correction : Ajout au panier (submitAddToCart) doit mettre à jour le panier immédiatement
function submitAddToCart(id_produit, id_pointure, quantite) {
    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'add',
            id: id_produit,
            pointure: id_pointure,
            quantite: quantite
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartFromServer(); // Met à jour le panier immédiatement
            showToast('Produit ajouté au panier !', 'success');
            var modal = bootstrap.Modal.getInstance(document.getElementById('addToCartModal'));
            if (modal) modal.hide();
        } else {
            showToast(data.message || 'Erreur lors de l\'ajout.', 'danger');
        }
    });
}

// Correction : updateCartQuantity et removeCartItem appellent updateCartFromServer pour affichage immédiat
function updateCartQuantity(id_produit, id_pointure, quantite) {
    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'update',
            id: id_produit,
            pointure: id_pointure,
            quantite: quantite
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartFromServer(); // Affichage immédiat
        }
    });
}

function removeCartItem(id_produit, id_pointure) {
    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'remove',
            id: id_produit,
            pointure: id_pointure
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartFromServer(); // Affichage immédiat
        }
    });
}

// Correction : updateCartFromServer appelle renderCartModal à chaque fois
function updateCartFromServer(callback) {
    fetch('cart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get'
    })
    .then(r => r.json())
    .then(data => {
        console.log('updateCartFromServer - data:', data); // DEBUG
        if (data.success) {
            window.sessionCart = data.cart;
            renderCartModal(); // Affichage immédiat
            if (typeof updateCartCountHeader === 'function') updateCartCountHeader();
            if (typeof callback === 'function') callback(data.cart);
        }
    });
}
// ...</script> 
</body>
</html>