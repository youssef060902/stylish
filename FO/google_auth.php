<?php
require_once 'vendor/autoload.php';
use Google\Service\Oauth2 as GoogleOauth2;

session_start();

$config = [
    'google_oauth' => [
        'client_id' => '906846133961-k9bem1jp506ssfele6gvk3c0mfsp9iue.apps.googleusercontent.com',
        'client_secret' => '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',
        'redirect_uri' => 'http://localhost/stylish-1.0.0/stylish-1.0.0/index.php'
    ]
];

$client = new Google_Client();
$client->setClientId($config['google_oauth']['client_id']);
$client->setClientSecret($config['google_oauth']['client_secret']);
$client->setRedirectUri($config['google_oauth']['redirect_uri']);
$client->addScope("email");
$client->addScope("profile");

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);
    
    $google_oauth = new GoogleOauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    
    $email = $google_account_info->email;
    $name = $google_account_info->name;
    
    // Ici, vous pouvez ajouter la logique pour connecter l'utilisateur
    // ou créer un nouveau compte si l'email n'existe pas
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    
    header('Location: index.php');
    exit();
}

// Générer l'URL d'authentification
$auth_url = $client->createAuthUrl(); 