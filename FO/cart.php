<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? ($_GET['action'] ?? 'get');

switch ($action) {
    case 'add':
        $id = intval($_POST['id']);
        $nom = $_POST['nom'];
        $prix = floatval($_POST['prix']);
        $image = $_POST['image'];
        $pointure = $_POST['pointure'];
        $quantite = intval($_POST['quantite']);
        // Cherche si déjà présent
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] == $id && $item['pointure'] == $pointure) {
                $item['quantite'] += $quantite;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $id,
                'nom' => $nom,
                'prix' => $prix,
                'image' => $image,
                'pointure' => $pointure,
                'quantite' => $quantite
            ];
        }
        echo json_encode(['success' => true, 'cart' => $_SESSION['cart']]);
        break;

    case 'update':
        $idx = intval($_POST['idx']);
        $quantite = intval($_POST['quantite']);
        if (isset($_SESSION['cart'][$idx])) {
            $_SESSION['cart'][$idx]['quantite'] = max(1, $quantite);
        }
        echo json_encode(['success' => true, 'cart' => $_SESSION['cart']]);
        break;

    case 'remove':
        $idx = intval($_POST['idx']);
        if (isset($_SESSION['cart'][$idx])) {
            array_splice($_SESSION['cart'], $idx, 1);
        }
        echo json_encode(['success' => true, 'cart' => $_SESSION['cart']]);
        break;

    case 'get':
    default:
        echo json_encode(['success' => true, 'cart' => $_SESSION['cart']]);
        break;
} 