<?php
require 'db.php';
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if (!isset($_SESSION['user_id'])) { die("Access Denied"); }

$payment_id = $_GET['id'] ?? null;
$tenant_id = $_SESSION['tenant_id'];

if ($payment_id) {
    $stmt = $pdo->prepare("
        SELECT repayments.*, clients.full_name, tenants.name as business_name 
        FROM repayments 
        JOIN loans ON repayments.loan_id = loans.id 
        JOIN clients ON loans.client_id = clients.id 
        JOIN tenants ON repayments.tenant_id = tenants.id
        WHERE repayments.id = ? AND repayments.tenant_id = ?
    ");
    $stmt->execute([$payment_id, $tenant_id]);
    $data = $stmt->fetch();

    if (!$data) { die("Malipo hayajapatikana."); }

    // Tengeneza Muonekano wa Risiti (HTML)
    $html = "
    <div style='font-family: sans-serif; padding: 20px; border: 1px solid #eee;'>
        <h2 style='text-align: center; color: #333;'>{$data['business_name']}</h2>
        <p style='text-align: center; font-size: 12px;'>Official Payment Receipt</p>
        <hr>
        <table style='width: 100%; margin-top: 20px;'>
            <tr><td><strong>Receipt No:</strong></td><td>#PAY-{$data['id']}</td></tr>
            <tr><td><strong>Mteja:</strong></td><td>{$data['full_name']}</td></tr>
            <tr><td><strong>Kiasi Kilicholipwa:</strong></td><td>TZS " . number_format($data['amount_paid'], 2) . "</td></tr>
            <tr><td><strong>Tarehe:</strong></td><td>" . date('d M, Y H:i', strtotime($data['payment_date'])) . "</td></tr>
        </table>
        <br><br>
        <p style='text-align: center; font-size: 10px; color: #777;'>!Welcome back! We are thrilled to have you here again!</p>
    </div>";

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A6', 'portrait'); 
    $dompdf->render();

    // Pakua PDF
    $dompdf->stream("Receipt_{$data['id']}.pdf", ["Attachment" => false]);
}
