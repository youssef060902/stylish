<?php
// -- SCRIPT DE DÉBOGAGE POUR L'ENVOI D'E-MAIL DE COMMANDE --

// 1. Activer l'affichage de toutes les erreurs PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Mode de débogage activé</h1>";
echo "<p>Ce script va tenter d'exécuter l'envoi d'e-mail pour une commande spécifique et afficher toutes les erreurs rencontrées.</p>";
echo "<hr>";

// --- Configuration ---
// !! IMPORTANT !!
// Modifiez la ligne ci-dessous pour mettre un ID de commande qui existe vraiment dans votre base de données.
$order_id_to_test = 1; 
// -------------------

echo "<p>Test en cours pour l'ID de commande : <strong>" . $order_id_to_test . "</strong></p>";

// 2. Simuler l'environnement d'exécution
// Le script original attend des données via POST, nous les simulons ici.
$_POST['id'] = $order_id_to_test;

// Démarrer une session factice pour passer la vérification d'authentification
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['admin_id'] = 1; // On suppose qu'un admin avec l'ID 1 existe.

echo "<p>Environnement simulé (POST et SESSION). Démarrage de l'inclusion de <code>send_order_details.php</code>...</p>";
echo "<hr>";
echo "<h2>Sortie du script :</h2><pre>";

// 3. Exécuter le script problématique
// Toutes les erreurs (notices, warnings, fatals) seront affichées ci-dessous.
include 'send_order_details.php';

echo "</pre>";
echo "<hr><h2>Fin de la sortie du script.</h2>";
echo "<p>Si la page est blanche après la ligne ci-dessus ou si l'e-mail n'est pas envoyé, copiez tout le contenu de cette page et envoyez-le moi.</p>";

?>
