<?php
ob_start(); // Démarrer la mise en mémoire tampon de la sortie au tout début

session_start();
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Autoloaders
require '../vendor/autoload.php';

$response = ['success' => false, 'message' => 'Erreur de départ.'];

if (!isset($_SESSION['admin_id'])) {
    $response['message'] = 'Accès interdit.';
    echo json_encode($response);
    exit();
}

if (!isset($_POST['id'])) {
    $response['message'] = 'ID de commande manquant.';
    echo json_encode($response);
    exit();
}

$order_id = $_POST['id'];

try {
    // Connexion DB
    $host = 'localhost';
    $dbname = 'stylish';
    $username = 'root';
    $password = '';
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les informations
    $stmt_order = $pdo->prepare("SELECT c.*, u.prenom, u.nom, u.email FROM commande c JOIN user u ON c.id_user = u.id WHERE c.id = :id");
    $stmt_order->execute(['id' => $order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    $stmt_products = $pdo->prepare("SELECT p.nom, po.pointure, cp.quantite, cp.prix_unitaire FROM commande_produit cp JOIN produit p ON cp.id_produit = p.id JOIN pointures po ON cp.id_pointure = po.id WHERE cp.id_commande = :id");
    $stmt_products->execute(['id' => $order_id]);
    $products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

    if (!$order || empty($products)) {
        throw new Exception('Détails de la commande introuvables.');
    }

    // --- Calculs des totaux (avec gestion des réductions) ---
    $subtotal = 0;
    foreach ($products as $product) {
        $subtotal += $product['prix_unitaire'] * $product['quantite'];
    }
    $shipping_cost = 7.00; // Coût de livraison fixe
    $total_in_db = $order['total'];
    $discount = ($subtotal + $shipping_cost) - $total_in_db;

    // Création du PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Stylish');
    $pdf->SetTitle('Facture Commande #' . $order_id);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(40, 40, 40);

    // Contenu HTML pour le PDF
    $logoUrl = 'https://i.ibb.co/vvZBxfg5/logoo.png';
    $html = '
<style>
    body { font-family: helvetica, sans-serif; font-size: 10pt; color: #282828; }
    .header { text-align: center; margin-bottom: 25px; }
    .invoice-title { font-size: 24pt; font-weight: bold; color: #333333; text-align:center; margin-bottom: 35px; }
    .info-table { width: 100%; margin-bottom: 35px; }
    .info-table h3 { font-size: 9pt; font-weight: bold; margin-bottom: 5px; color: #555555; text-transform: uppercase; }
    .info-table p { line-height: 1.4; }
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
    .items-table th { background-color: #f5f5f5; color: #333; font-weight: bold; text-align: left; padding: 10px; border-bottom: 2px solid #dddddd; }
    .items-table td { padding: 10px; border-bottom: 1px solid #eeeeee; }
    .totals-table { width: 60%; border-collapse: collapse; }
    .totals-table td { padding: 8px; }
    .totals-table .label { text-align: left; font-weight: bold; color: #555; }
    .totals-table .value { text-align: right; }
    .grand-total { font-weight: bold; font-size: 12pt; }
    .footer { text-align: center; margin-top: 40px; font-size: 8pt; color: #888; border-top: 1px solid #dddddd; padding-top: 15px; }
</style>
<body>
    <div class="header">
        <img src="' . $logoUrl . '" alt="logo" style="width:180px;">
    </div>

    <div class="invoice-title">FACTURE</div>

    <table class="info-table">
        <tr>
            <td style="width:50%;">
                <h3>De</h3>
                <p>
                    <strong>Stylish Store</strong><br>
                    123 Rue du Commerce<br>
                    Tunis, 1001, Tunisie<br>
                    Tél: +216 22 123 456
                </p>
            </td>
            <td style="width:50%; text-align: right;">
                <h3>Facturé à</h3>
                <p>
                    <strong>' . htmlspecialchars($order['prenom']) . ' ' . htmlspecialchars($order['nom']) . '</strong><br>
                    ' . nl2br(htmlspecialchars($order['adresse_livraison'])) . '<br>
                    ' . htmlspecialchars($order['email']) . '
                </p>
            </td>
        </tr>
        <tr>
             <td colspan="2" style="padding-top:20px; text-align:right;">
                <strong>Facture N° :</strong> ' . $order['id'] . '<br>
                <strong>Date :</strong> ' . date('d/m/Y', strtotime($order['date_commande'])) . '
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:55%;">Article</th>
                <th style="width:15%; text-align:center;">Pointure</th>
                <th style="width:15%; text-align:center;">Qté</th>
                <th style="width:15%; text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($products as $product) {
        $html .= '
            <tr>
                <td style="width:55%;">' . htmlspecialchars($product['nom']) . '</td>
                <td style="width:15%; text-align:center;">' . htmlspecialchars($product['pointure']) . '</td>
                <td style="width:15%; text-align:center;">' . $product['quantite'] . '</td>
                <td style="width:15%; text-align:right;">' . number_format($product['prix_unitaire'] * $product['quantite'], 2, ',', ' ') . ' DT</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>
    
    <table style="width:100%;">
        <tr>
            <td align="center">
                <table class="totals-table">
                    <tr>
                        <td class="label" style="width:70%;">Sous-total :</td>
                        <td class="value" style="width:30%;">' . number_format($subtotal, 2, ',', ' ') . ' DT</td>
                    </tr>
                    <tr>
                        <td class="label">Livraison :</td>
                        <td class="value">' . number_format($shipping_cost, 2, ',', ' ') . ' DT</td>
                    </tr>';

    if ($discount > 0.01) {
        $html .= '
                    <tr>
                        <td class="label" style="color:#d9534f;">Réduction :</td>
                        <td class="value" style="color:#d9534f;">- ' . number_format($discount, 2, ',', ' ') . ' DT</td>
                    </tr>';
    }

    $html .= '
                    <tr><td colspan="2" style="border-top: 1px solid #eeeeee; padding-top:10px;">&nbsp;</td></tr>
                    <tr class="grand-total">
                        <td class="label">Total Payé :</td>
                        <td class="value">' . number_format($total_in_db, 2, ',', ' ') . ' DT</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Merci pour votre confiance !</p>
        <p>Stylish Store - Tous droits réservés &copy; ' . date('Y') . '</p>
    </div>
</body>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdfContent = $pdf->Output('commande_'.$order_id.'.pdf', 'S');

    // Envoi de l'e-mail
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'youssefcarma@gmail.com';
    $mail->Password   = 'oupl cahg lkac cxun';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('no-reply@stylish.com', 'Stylish');
    $mail->addAddress($order['email'], $order['prenom'] . ' ' . $order['nom']);
    $mail->addStringAttachment($pdfContent, 'facture_commande_'.$order_id.'.pdf', 'base64', 'application/pdf');

    $mail->isHTML(true);
    $mail->Subject = 'Votre facture pour la commande Stylish #' . $order_id;
    $mail->Body    = 'Bonjour '.htmlspecialchars($order['prenom']).',<br><br>Veuillez trouver ci-joint la facture pour votre commande n°'.$order_id.'.<br><br>Merci de votre confiance.<br><br>Cordialement,<br>L\'équipe Stylish';

    $mail->send();
    
    $response['success'] = true;
    $response['message'] = 'E-mail envoyé avec succès.';

} catch (Throwable $e) {
    // Capturer TOUTES les erreurs (PDO, TCPDF, PHPMailer, etc.)
    $response['message'] = 'Erreur: ' . $e->getMessage() . ' (Fichier: ' . basename($e->getFile()) . ', Ligne: ' . $e->getLine() . ')';
    error_log($e); // Enregistrer l'erreur complète dans les logs du serveur
}

// --- Nettoyage final et envoi de la réponse JSON ---

// 1. Nettoyer toute sortie inattendue (espaces, erreurs de librairies, etc.)
ob_clean();

// 2. Envoyer l'en-tête JSON maintenant que la sortie est propre
header('Content-Type: application/json');

// 3. Envoyer la réponse JSON finale
echo json_encode($response);

// 4. Terminer le script
exit();
?> 