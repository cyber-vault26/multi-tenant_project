<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$tenant_id = $_SESSION['tenant_id'];

$sql = "SELECT loans.*, clients.full_name as client_name 
        FROM loans 
        JOIN clients ON loans.client_id = clients.id 
        WHERE loans.tenant_id = ? 
        ORDER BY loans.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tenant_id]);
$loans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Loans — Strong Bridge ERP</title>
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
                <h1 class="text-2xl font-bold text-white">Loans Management</h1>
                <p class="text-sm text-slate-500">Orodha ya mikopo yote iliyotolewa.</p>
	    </div>
             <div class="flex gap-4">
        <a href="collect-payment.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z"></path></svg>
            Collect Payment
        </a>

        <!-- Kitufe cha Issue Loan (Bluu) -->
        <a href="issue-loan.php" class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Issue New Loan
        </a>
    </div>

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
            <table class="w-full text-left border-collapse text-sm">
                <thead class="bg-white/5 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="p-5">Customer</th>
                        <th class="p-5">Amount (TZS)</th>
                        <th class="p-5">Interest</th>
                        <th class="p-5">Muda</th>
                        <th class="p-5">Status</th>
                        <th class="p-5">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loans as $loan): ?>
                    <tr class="border-t border-white/5 hover:bg-white/5 transition-all">
                        <td class="p-5 font-medium"><?php echo htmlspecialchars($loan['client_name']); ?></td>
                        <td class="p-5"><?php echo number_format($loan['amount'], 2); ?></td>
                        <td class="p-5"><?php echo $loan['interest_rate']; ?>%</td>
                        <td class="p-5"><?php echo $loan['duration_months']; ?> Miezi</td>
                        <td class="p-5">
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?php echo $loan['status'] == 'active' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500'; ?>">
                                <?php echo $loan['status']; ?>
                            </span>
			</td>
                        <td class="p-5 flex gap-3">
                       <a href="loan-details.php?id=<?php echo $loan['id']; ?>" class="text-sky-400 hover:underline font-medium">Manage</a>
                     
                      <a href="collect-payment.php?loan_id=<?php echo $loan['id']; ?>" class="text-emerald-500 hover:underline font-medium">
                        Receive Pay
                    </a>
                </td>
       
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($loans)): ?>
                        <tr><td colspan="6" class="p-10 text-center text-slate-500">Hakuna mikopo iliyotolewa bado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
