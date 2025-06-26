<?php
// Paramètres de la base de données
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'stylish';

// Connexion MySQLi (utilisée par la plupart du site)
$conn = new mysqli($host, $user, $pass, $db);

// Vérification de la connexion MySQLi
if ($conn->connect_error) {
    die("La connexion MySQLi a échoué : " . $conn->connect_error);
}

// Connexion PDO (pour les parties qui l'utilisent)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("La connexion PDO a échoué : " . $e->getMessage());
}
?> 