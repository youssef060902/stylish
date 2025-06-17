<?php
$host = 'localhost';
$dbname = 'stylish';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        throw new Exception("Erreur de connexion : " . $conn->connect_error);
    }
} catch(Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?> 