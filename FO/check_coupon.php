<?php
$code = trim($_POST['coupon'] ?? '');
$response = ['valid' => false, 'message' => '', 'discount' => 0];
if ($code === '') {
    $response['message'] = "Veuillez saisir un code coupon.";
} else {
    $pdo = new PDO("mysql:host=localhost;dbname=stylish", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare("SELECT discount, statut FROM coupon WHERE code = ?");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($coupon && $coupon['discount'] > 0 && $coupon['statut'] === 'active') {
        $response['valid'] = true;
        $response['discount'] = (float)$coupon['discount'];
        $response['message'] = "Coupon appliqué : -" . (int)$coupon['discount'] . "%";
    } elseif ($coupon && $coupon['statut'] !== 'active') {
        $response['message'] = "Ce coupon est inactif.";
    } else {
        $response['message'] = "Code coupon invalide.";
    }
}
header('Content-Type: application/json');
echo json_encode($response); 