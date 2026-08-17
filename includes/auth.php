<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$tenant_id = $_SESSION['tenant_id']; // key of multi-tenancy
?>
