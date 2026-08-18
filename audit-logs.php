<?php
require 'db.php';
session_start();

// Ulinzi: Admin tu
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];

// Pata logs na majina ya waliofanya matendo
$stmt = $pdo->prepare("
    SELECT audit_logs.*, users.full_name 
    FROM audit_logs 
    JOIN users ON audit_logs.user_id = users.id 
    WHERE audit_logs.tenant_id = ? 
    ORDER BY audit_logs.created_at DESC LIMIT 100
");
$stmt->execute([$tenant_id]);
$logs = $stmt->fetchAll();
require_once 'includes/functions.php';
logAction($pdo, "Staff Added", "Registered new staff: " . $fullName);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
    <?php include 'includes/sidebar.php'; ?>

    <main class="lg:ml-64 p-8">
        <header class="mb-10">
            <h1 class="text-3xl font-bold">Audit Logs</h1>
            <p class="text-slate-500 text-sm">Fuatilia kila shughuli inayofanyika kwenye mfumo wako.</p>
        </header>

        <div class="glass-card rounded-[2rem] overflow-hidden text-sm">
            <table class="w-full text-left">
                <thead class="bg-white/5 text-slate-500 uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="p-5">Wakati</th>
                        <th class="p-5">Mfanyakazi</th>
                        <th class="p-5">Tendo (Action)</th>
                        <th class="p-5">Maelezo</th>
                        <th class="p-5">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($logs as $l): ?>
                    <tr class="border-t border-white/5 hover:bg-white/5 transition-all">
                        <td class="p-5 text-slate-400"><?php echo date('M d, H:i:s', strtotime($l['created_at'])); ?></td>
                        <td class="p-5 font-bold text-sky-400"><?php echo htmlspecialchars($l['full_name']); ?></td>
                        <td class="p-5">
                            <span class="bg-white/10 px-2 py-1 rounded text-[10px] font-mono"><?php echo $l['action']; ?></span>
                        </td>
                        <td class="p-5 text-slate-300"><?php echo htmlspecialchars($l['details']); ?></td>
                        <td class="p-5 text-slate-500 font-mono text-[10px]"><?php echo $l['ip_address']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
