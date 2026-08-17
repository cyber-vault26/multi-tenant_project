<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];

$product_id = $_GET['id'] ?? null;

if ($product_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$product_id, $tenant_id]);

         header("Location: inventory.php?msg=deleted");
        exit();

    } catch (PDOException $e) {
         if ($e->getCode() == 23000) {
            die("<script>alert('You cannot delete this product because it has already been used in sales records.'); window.location.href='inventory.php';</script>");
        } else {
            die("Error: " . $e->getMessage());
        }
    }
} else {
    header("Location: inventory.php");
    exit();
}
