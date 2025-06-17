<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();
ob_start();

set_exception_handler(function ($exception) {
    ob_end_clean();
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur interne du serveur est survenue. (Exception)',
        'error_details' => $exception->getMessage() // Pour le débogage, à retirer en production
    ]);
    error_log("Exception non gérée dans login.php: " . $exception->getMessage() . " sur " . $exception->getFile() . " ligne " . $exception->getLine());
    die();
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        // Cette erreur n'est pas incluse dans error_reporting
        return false;
    }
    // Convertir les erreurs PHP en exceptions pour qu'elles soient gérées par le handler d'exceptions
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// require_once 'vendor/autoload.php'; // COMMENTÉ POUR LE DÉBOGAGE
// use Google\\Service\\Oauth2 as GoogleOauth2; // COMMENTÉ POUR LE DÉBOGAGE

// $config = [ // COMMENTÉ POUR LE DÉBOGAGE
//     'google_oauth' => [
//         'client_id' => '906846133961-k9bem1jp506ssfele6gvk3c0mfsp9iue.apps.googleusercontent.com',
//         'client_secret' => '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',
//         'redirect_uri' => 'http://localhost/stylish-1.0.0/stylish-1.0.0/login.php'
//     ]
// ];

// $client = new Google_Client(); // COMMENTÉ POUR LE DÉBOGAGE
// $client->setClientId($config['google_oauth']['client_id']); // COMMENTÉ POUR LE DÉBOGAGE
// $client->setClientSecret($config['google_oauth']['client_secret']); // COMMENTÉ POUR LE DÉBOGAGE
// $client->setRedirectUri($config['google_oauth']['redirect_uri']); // COMMENTÉ POUR LE DÉBOGAGE
// $client->addScope("email"); // COMMENTÉ POUR LE DÉBOGAGE
// $client->addScope("profile"); // COMMENTÉ POUR LE DÉBOGAGE

// Traitement de l'authentification Google OAuth - COMMENTÉ POUR LE DÉBOGAGE
if (isset($_GET['code'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Google OAuth est temporairement désactivé pour le débogage.']);
    die();
}
// Traitement de la connexion normale via POST
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier si les champs sont définis
    if (!isset($_POST['login_email']) || !isset($_POST['login_password'])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Email ou mot de passe manquant.']);
        die();
    }

    $email = filter_var($_POST['login_email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['login_password'];

    // Ajout d'un log de test pour le débogage
    error_log("Tentative de connexion pour l'email: " . $email);

    try {
        $conn = new mysqli("localhost", "root", "", "stylish");
        if ($conn->connect_error) {
            throw new Exception("Erreur de connexion à la base de données : " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Erreur de préparation de la requête (Login normal).");
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect.']);
            die();
        }

        // Vérification simple du mot de passe (sans hachage, selon votre demande)
        if ($user['password'] !== $password) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Email ou mot de passe incorrect.']);
            die();
        }

        // Enregistrer les informations en session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_image'] = $user['image'] ?? null;
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_genre'] = $user['genre'] ?? 'non spécifié';
        $_SESSION['user_date_naissance'] = $user['date_naissance'] ?? '0000-00-00';
        $_SESSION['user_phone'] = $user['phone'] ?? 'non spécifié';
        $_SESSION['user_adresse'] = $user['adresse'] ?? 'non spécifié';
        $_SESSION['user_age'] = $user['age'] ?? 0;

        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        die();
    } catch (Exception $e) {
        error_log("Erreur de connexion : " . $e->getMessage());
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la connexion.', 'error_details' => $e->getMessage()]);
        die();
    } finally {
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
            $stmt->close();
        }
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->close();
        }
    }
}
// Redirection par défaut si le script est accédé sans méthode POST ou code OAuth
else {
    ob_end_clean(); // S'assurer que le tampon de sortie est propre avant la redirection
    header('Location: index.php'); // Rediriger vers la page d'accueil ou de connexion appropriée
    die();
}

?>
