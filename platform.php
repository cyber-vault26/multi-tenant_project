<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    die("You do not have authority to enter here. This section is for system owners only.");
}

$total_tenants = $pdo->query("SELECT COUNT(*) FROM tenants WHERE id > 1")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM sales")->fetchColumn() ?? 0;
$total_loans = $pdo->query("SELECT SUM(amount) FROM loans")->fetchColumn() ?? 0;

$tenants = $pdo->query("SELECT * FROM tenants WHERE id > 1 ORDER BY created_at DESC LIMIT 10")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Admin — Strong Bridge Control</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }</style>
</head>
<body class="p-8 lg:ml-64 bg-slate-950 min-h-screen text-slate-200">
    <?php include 'includes/sidebar.php'; ?>

    <div class="max-w-6xl mx-auto">
        <header class="mb-10">
            <h1 class="text-4xl font-black text-white tracking-tighter">Platform Control Center</h1>
            <p class="text-slate-500 mt-2 text-lg">Overview of the Entire ERP System</p>
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

        <!-- System Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
            <div class="glass p-8 rounded-[2.5rem] border-t-4 border-sky-500">
                <p class="text-xs text-slate-500 uppercase font-bold">Registered Orgs</p>
                <h3 class="text-4xl font-bold mt-2"><?php echo $total_tenants; ?></h3>
            </div>
            <div class="glass p-8 rounded-[2.5rem] border-t-4 border-indigo-500">
                <p class="text-xs text-slate-500 uppercase font-bold">Total Users</p>
                <h3 class="text-4xl font-bold mt-2"><?php echo $total_users; ?></h3>
            </div>
            <div class="glass p-8 rounded-[2.5rem] border-t-4 border-emerald-500">
                <p class="text-xs text-slate-500 uppercase font-bold">Total Sales (Global)</p>
                <h3 class="text-2xl font-bold mt-2 text-emerald-400">TZS <?php echo number_format($total_revenue); ?></h3>
            </div>
            <div class="glass p-8 rounded-[2.5rem] border-t-4 border-amber-500">
                <p class="text-xs text-slate-500 uppercase font-bold">Global Loan Book</p>
                <h3 class="text-2xl font-bold mt-2 text-amber-500">TZS <?php echo number_format($total_loans); ?></h3>
            </div>
        </div>

        <!-- Registered Tenants Table -->
        <div class="glass rounded-[2.5rem] overflow-hidden">
            <div class="p-8 border-b border-white/5 flex justify-between items-center">
                <h3 class="text-xl font-bold">Active Organizations</h3>
                <span class="bg-sky-500/10 text-sky-400 px-4 py-1 rounded-full text-xs font-bold">Live Monitoring</span>
            </div>
            <table class="w-full text-left">
                <thead class="bg-white/5 text-slate-500 uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="p-6">Organization Name</th>
                        <th class="p-6">Registered On</th>
                        <th class="p-6">Status</th>
                        <th class="p-6">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tenants as $t): ?>
                    <tr class="border-t border-white/5 hover:bg-white/5 transition-all">
                        <td class="p-6 font-bold text-white"><?php echo htmlspecialchars($t['name']); ?></td>
                        <td class="p-6 text-slate-400"><?php echo date('d M, Y', strtotime($t['created_at'])); ?></td>
                        <td class="p-6"><span class="bg-emerald-500/10 text-emerald-500 px-3 py-1 rounded-lg text-[10px] font-bold uppercase">Active</span></td>
                        <td class="p-6 text-sky-400 cursor-pointer hover:underline font-bold">Manage Org</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
