<?php
session_start();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour modifier vos paramètres.']);
    exit;
}

try {
    // Récupération des données du formulaire
    $prenom = $_POST['prenom'];
    $nom = $_POST['nom'];
    $genre = $_POST['genre'];
    $date_naissance = $_POST['date_naissance'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $adresse = $_POST['adresse'];
    $user_id = $_SESSION['user_id'];

    // Vérification si l'email existe déjà pour un autre utilisateur
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé par un autre compte.']);
        exit;
    }

    // Calcul de l'âge à partir de la date de naissance
    $birthDate = new DateTime($date_naissance);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;

    // Gestion de l'image de profil (optionnelle et suppression)
    $image_path = null;
    
    // Récupérer l'image actuelle de l'utilisateur
    $stmt = $conn->prepare("SELECT image FROM user WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($current_image);
    $stmt->fetch();
    $stmt->close();
    
    if (!empty($_POST['delete_image']) && ($_POST['delete_image'] === '1' || $_POST['delete_image'] === 'true')) {
        // Supprimer l'ancienne image si elle existe
        if ($current_image && file_exists($current_image)) {
            @unlink($current_image);
        }
        $image_path = null;
    }
    else if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            echo json_encode([
                'success' => false,
                'message' => 'Format d\'image non supporté. Utilisez JPG, PNG ou GIF',
                'field' => 'image'
            ]);
            exit;
        }

        if ($_FILES['image']['size'] > $max_size) {
            echo json_encode([
                'success' => false,
                'message' => 'L\'image ne doit pas dépasser 5MB',
                'field' => 'image'
            ]);
            exit;
        }

        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/img/'; // Chemin absolu vers C:/xampp/htdocs/img
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('user_') . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_path = 'http://localhost/img/' . $new_filename;
        }
    } else {
        // Si aucune nouvelle image n'est uploadée et pas de suppression demandée, garder l'image existante
        $image_path = $current_image;
    }

    // Mise à jour des informations dans la base de données
    $stmt = $conn->prepare("UPDATE user SET 
        prenom = ?, 
        nom = ?, 
        genre = ?, 
        date_naissance = ?, 
        age = ?,
        email = ?, 
        phone = ?, 
        adresse = ?, 
        image = ? 
        WHERE id = ?");

    // Vérifier si la préparation de la requête a réussi
    if ($stmt === false) {
        throw new Exception("Erreur de préparation de la requête : " . $conn->error);
    }

    $stmt->bind_param("ssssissssi", 
        $prenom,
        $nom,
        $genre,
        $date_naissance,
        $age,
        $email,
        $phone,
        $adresse,
        $image_path,
        $user_id
    );

    if ($stmt->execute()) {
        // Mise à jour des variables de session AVANT d'envoyer la réponse JSON
        $_SESSION['user_prenom'] = $prenom;
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_genre'] = $genre;
        $_SESSION['user_date_naissance'] = $date_naissance;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['user_adresse'] = $adresse;
        $_SESSION['user_image'] = $image_path; // Mettre à jour le chemin de l'image dans la session

        echo json_encode(['success' => true, 'message' => 'Vos informations ont été mises à jour avec succès.']);
    } else {
        throw new Exception("Erreur lors de la mise à jour des informations dans la base de données : " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    // Afficher les erreurs spécifiques
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour des informations : ' . $e->getMessage()]);
}
?> 