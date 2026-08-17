<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'staff') {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];

// Pata salio la kila akaunti ya kampuni hii
$stmt = $pdo->prepare("SELECT * FROM accounts WHERE tenant_id = ? ORDER BY account_type ASC");
$stmt->execute([$tenant_id]);
$accounts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>General Ledger — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; font-family: 'Inter', sans-serif; }
        .glass { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
    <?php include 'includes/sidebar.php'; ?>

    <main class="lg:ml-64 p-8">
        <header class="mb-10">
            <h1 class="text-3xl font-bold">General Ledger (Uhasibu)</h1>
            <p class="text-slate-500 text-sm">Hali ya kifedha ya akaunti zako zote.</p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach($accounts as $acc): ?>
            <div class="glass p-6 rounded-[2rem] border-t-4 <?php 
                echo ($acc['account_type'] == 'Asset') ? 'border-sky-500' : 'border-emerald-500'; 
            ?>">
                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest"><?php echo $acc['account_type']; ?></p>
                <h3 class="text-lg font-bold mt-1"><?php echo htmlspecialchars($acc['account_name']); ?></h3>
                <h2 class="text-2xl font-black mt-4 text-white">
                    TZS <?php echo number_format($acc['balance'], 2); ?>
                </h2>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if(empty($accounts)): ?>
            <div class="mt-10 p-10 glass rounded-[2rem] text-center text-slate-500 italic">
                Hakuna akaunti zilizopatikana. Tafadhali nenda kwenye Settings ukafanye setup ya kuanzia.
            </div>
        <?php endif; ?>
    </main>
</body>
</html>