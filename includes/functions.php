<?php
function logAction($pdo, $action, $details = "") {
    if (!isset($_SESSION['user_id'])) return;

    $tenant_id = $_SESSION['tenant_id'];
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt = $pdo->prepare("INSERT INTO audit_logs (tenant_id, user_id, action, details, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$tenant_id, $user_id, $action, $details, $ip]);
}
?>
