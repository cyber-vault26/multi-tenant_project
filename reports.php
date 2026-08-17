<?php
require 'db.php';
session_start();
if ($_SESSION['role'] === 'staff') {
    die("<div style='background:#020617; color:white; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif;'>
            <div style='text-align:center;'>
                <h1 style='font-size:3rem;'>🚫</h1>
                <p>Huna mamlaka ya kuona ripoti za kampuni.</p>
                <a href='dashboard.php' style='color:#38bdf8;'>Rudi Dashboard</a>
            </div>
         </div>");
}
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];

//Total Principal Issued
$stmt = $pdo->prepare("SELECT SUM(amount) FROM loans WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$total_principal = $stmt->fetchColumn() ?? 0;

// Total Collections
$stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM repayments WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$total_collected = $stmt->fetchColumn() ?? 0;

// Expected Interest
$stmt = $pdo->prepare("SELECT SUM(amount * (interest_rate / 100)) FROM loans WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$expected_interest = $stmt->fetchColumn() ?? 0;

//Daily Collections
$stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM repayments WHERE tenant_id = ? AND DATE(payment_date) = CURDATE()");
$stmt->execute([$tenant_id]);
$today_collection = $stmt->fetchColumn() ?? 0;

$total_expected = $total_principal + $expected_interest;
$outstanding_balance = $total_expected - $total_collected;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Financial Reports — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }</style>
</head>
<body class="text-slate-200">
    <?php include 'includes/sidebar.php'; ?>

    <main class="lg:ml-64 p-8">
        <header class="mb-10">
            <h1 class="text-3xl font-bold">Financial Analytics</h1>
            <p class="text-slate-500">Report on the flow of funds in your workspace.</p>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="glass p-6 rounded-3xl border-b-4 border-emerald-500">
                <p class="text-xs text-slate-500 uppercase font-bold">Today's Collection</p>
                <h3 class="text-2xl font-bold mt-2 text-emerald-400">TZS <?php echo number_format($today_collection); ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl">
                <p class="text-xs text-slate-500 uppercase font-bold">Total Collected</p>
                <h3 class="text-2xl font-bold mt-2 text-white">TZS <?php echo number_format($total_collected); ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-b-4 border-sky-500">
                <p class="text-xs text-slate-500 uppercase font-bold">Outstanding</p>
                <h3 class="text-2xl font-bold mt-2 text-sky-400">TZS <?php echo number_format($outstanding_balance); ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-b-4 border-amber-500">
                <p class="text-xs text-slate-500 uppercase font-bold">Expected Interest</p>
                <h3 class="text-2xl font-bold mt-2 text-amber-500">TZS <?php echo number_format($expected_interest); ?></h3>
            </div>
        </div>

        <!-- Visual Progress -->
        <div class="glass p-8 rounded-[2.5rem]">
            <h3 class="font-bold mb-6">Collection Progress</h3>
            <?php 
                $progress = ($total_expected > 0) ? ($total_collected / $total_expected) * 100 : 0;
            ?>
            <div class="w-full bg-slate-800 rounded-full h-4">
                <div class="bg-sky-500 h-4 rounded-full" style="width: <?php echo $progress; ?>%"></div>
            </div>
            <p class="text-sm mt-4 text-slate-400">You have collected <strong><?php echo round($progress, 1); ?>%</strong> of all the money you are claiming (Principal + Interest).</p>
        </div>
    </main>
</body>
</html>
