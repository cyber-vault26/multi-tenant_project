<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$tenant_id = $_SESSION['tenant_id'];

$stmt = $pdo->prepare("SELECT * FROM clients WHERE tenant_id = ? ORDER BY created_at DESC");
$stmt->execute([$tenant_id]);
$clients = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients — Strong Bridge ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
    <?php include 'includes/sidebar.php'; ?>

    <main class="lg:ml-64 p-8">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-2xl font-bold text-white">Clients</h1>
                <p class="text-sm text-slate-500">Manage your workspace clients.</p>
            </div>
            <a href="add-client.php" class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all">
                + Register New client
            </a>
	</header>
       <!-- Kitufe cha Menu kwa ajili ya Simu -->
<div class="lg:hidden flex items-center justify-between p-4 bg-slate-900 border-b border-white/5 mb-4 rounded-xl">
    <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-sky-500 rounded-lg flex items-center justify-center font-bold text-slate-950 text-xs">SB</div>
        <span class="font-bold text-sm">Strong Bridge</span>
    </div>
    <button onclick="toggleSidebar()" class="p-2 text-slate-400 hover:bg-white/5 rounded-lg">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
    </button>
</div>

        <div class="glass-card rounded-[2rem] overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-white/5 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="p-5">FUll Name</th>
                        <th class="p-5">Phone</th>
                        <th class="p-5">NIDA/ID</th>
                        <th class="p-5">Date of registration</th>
                        <th class="p-5">Action</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php foreach ($clients as $client): ?>
                    <tr class="border-t border-white/5 hover:bg-white/5 transition-all">
                        <td class="p-5 font-medium"><?php echo htmlspecialchars($client['full_name']); ?></td>
                        <td class="p-5"><?php echo htmlspecialchars($client['phone']); ?></td>
                        <td class="p-5"><?php echo htmlspecialchars($client['id_number']); ?></td>
                        <td class="p-5 text-slate-500"><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                        <td class="p-5 text-sky-400 cursor-pointer">View</td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="5" class="p-10 text-center text-slate-500">No customer has been registered yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
