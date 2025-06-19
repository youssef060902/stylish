<?php
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

class InvoicePDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 20);
        $this->Cell(0, 15, 'FACTURE', 0, true, 'C', 0, '', 0, false, 'M', 'M');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 10, 'Stylish Store', 0, true, 'C');
        $this->Ln(10);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

function generateInvoicePDF($commande_id) {
    $pdo = new PDO("mysql:host=localhost;dbname=stylish", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Récupérer les informations de la commande
    $stmt = $pdo->prepare("SELECT c.*, u.email, u.nom as user_nom, u.prenom 
                          FROM commande c 
                          JOIN user u ON c.id_user = u.id 
                          WHERE c.id = ?");
    $stmt->execute([$commande_id]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les produits de la commande avec leurs pointures
    $stmt = $pdo->prepare("SELECT cp.*, p.nom as produit_nom, po.pointure 
                          FROM commande_produit cp 
                          JOIN produit p ON cp.id_produit = p.id 
                          JOIN pointures po ON cp.id_pointure = po.id 
                          WHERE cp.id_commande = ?");
    $stmt->execute([$commande_id]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Créer le PDF
    $pdf = new InvoicePDF();
    $pdf->SetCreator('Stylish Store');
    $pdf->SetAuthor('Stylish Store');
    $pdf->SetTitle('Facture #' . $commande_id);

    $pdf->AddPage();

    // En-tête avec informations client
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Facture N° ' . $commande_id, 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Date : ' . date('d/m/Y', strtotime($commande['date_commande'])), 0, 1, 'L');
    $pdf->Cell(0, 10, 'Client : ' . $commande['prenom'] . ' ' . $commande['user_nom'], 0, 1, 'L');
    $pdf->Cell(0, 10, 'Email : ' . $commande['email'], 0, 1, 'L');
    $pdf->Cell(0, 10, 'Adresse de livraison : ' . $commande['adresse_livraison'], 0, 1, 'L');
    $pdf->Ln(10);

    // Tableau des produits
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(70, 7, 'Produit', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Pointure', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Prix unit.', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'Qté', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Total', 1, 1, 'C', true);

    $pdf->SetFont('helvetica', '', 10);
    $total = 0;
    foreach ($produits as $produit) {
        $pdf->Cell(70, 7, $produit['produit_nom'], 1, 0, 'L');
        $pdf->Cell(20, 7, $produit['pointure'], 1, 0, 'C');
        $pdf->Cell(30, 7, number_format($produit['prix_unitaire'], 2) . ' DT', 1, 0, 'R');
        $pdf->Cell(20, 7, $produit['quantite'], 1, 0, 'C');
        $subtotal = $produit['prix_unitaire'] * $produit['quantite'];
        $pdf->Cell(40, 7, number_format($subtotal, 2) . ' DT', 1, 1, 'R');
        $total += $subtotal;
    }

    // Totaux
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, 'Sous-total', 0, 0, 'R');
    $pdf->Cell(40, 7, number_format($total, 2) . ' DT', 0, 1, 'R');

    // Calcul de la réduction (différence entre total et sous-total + livraison)
    $reduction = $total + 7.00 - $commande['total'];
    if ($reduction > 0) {
        $pdf->Cell(140, 7, 'Réduction', 0, 0, 'R');
        $pdf->Cell(40, 7, '-' . number_format($reduction, 2) . ' DT', 0, 1, 'R');
    }

    $pdf->Cell(140, 7, 'Frais de livraison', 0, 0, 'R');
    $pdf->Cell(40, 7, '+7.00 DT', 0, 1, 'R');

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(140, 10, 'Total', 0, 0, 'R');
    $pdf->Cell(40, 10, number_format($commande['total'], 2) . ' DT', 0, 1, 'R');

    // Pied de page avec mentions légales
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->MultiCell(0, 5, "Merci de votre confiance !\nPour toute question concernant votre commande, contactez-nous à contact@stylish.com", 0, 'C');

    return $pdf->Output('Facture_' . $commande_id . '.pdf', 'S');
} 