<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'staff') {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];

$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$total_sales_revenue = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("SELECT SUM(amount * (interest_rate / 100)) FROM loans WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$total_interest_income = $stmt->fetchColumn() ?? 0;

$total_gross_income = $total_sales_revenue + $total_interest_income;


$stmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$total_expenses = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("
    SELECT accounts.account_name, SUM(expenses.amount) as total 
    FROM expenses 
    JOIN accounts ON expenses.account_id = accounts.id 
    WHERE expenses.tenant_id = ? 
    GROUP BY accounts.id
");
$stmt->execute([$tenant_id]);
$expense_breakdown = $stmt->fetchAll();


$net_profit = $total_gross_income - $total_expenses;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profit & Loss — Strong Bridge ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; font-family: 'Inter', sans-serif; }
        .glass { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="p-8 lg:ml-64 bg-slate-950 min-h-screen text-slate-200">
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
    <?php include 'includes/sidebar.php'; ?>

    <div class="max-w-4xl mx-auto">
        <header class="mb-10 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold">Profit & Loss Statement</h1>
                <p class="text-slate-500">Your business's financial summary</p>
            </div>
            <div class="text-right">
                <p class="text-[10px] uppercase font-bold text-slate-500 tracking-widest">Reporting Period</p>
                <p class="font-bold text-sky-400">All Time (Cumulative)</p>
            </div>
        </header>

        <!-- Main Profit Card -->
        <div class="glass p-10 rounded-[2.5rem] mb-10 border-t-4 <?php echo $net_profit >= 0 ? 'border-emerald-500' : 'border-red-500'; ?>">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm text-slate-400">Net Profit</p>
                    <h2 class="text-5xl font-black mt-2 <?php echo $net_profit >= 0 ? 'text-emerald-400' : 'text-red-400'; ?>">
                        TZS <?php echo number_format($net_profit, 2); ?>
                    </h2>
                </div>
                <div class="bg-white/5 p-4 rounded-3xl">
                    <svg class="w-12 h-12 <?php echo $net_profit >= 0 ? 'text-emerald-500' : 'text-red-500'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Mapato Section -->
            <div class="glass p-8 rounded-[2rem]">
                <h3 class="text-lg font-bold mb-6 text-sky-400 border-b border-white/5 pb-4">Total Income</h3>
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Retail Sales</span>
                        <span class="font-bold">TZS <?php echo number_format($total_sales_revenue); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Loan Interest</span>
                        <span class="font-bold">TZS <?php echo number_format($total_interest_income); ?></span>
                    </div>
                    <div class="pt-4 border-t border-white/5 flex justify-between text-xl font-bold">
                        <span>Total Income</span>
                        <span class="text-white">TZS <?php echo number_format($total_gross_income); ?></span>
                    </div>
                </div>
            </div>

            <!-- Matumizi Section -->
            <div class="glass p-8 rounded-[2rem]">
                <h3 class="text-lg font-bold mb-6 text-red-400 border-b border-white/5 pb-4">Total Expenses</h3>
                <div class="space-y-4">
                    <?php foreach($expense_breakdown as $exp): ?>
                    <div class="flex justify-between">
                        <span class="text-slate-400"><?php echo $exp['account_name']; ?></span>
                        <span class="font-bold">TZS <?php echo number_format($exp['total']); ?></span>
                    </div>
                    <?php endforeach; ?>

                    <?php if(empty($expense_breakdown)): ?>
                        <p class="text-sm text-slate-500 italic">No usage has been recorded.</p>
                    <?php endif; ?>

                    <div class="pt-4 border-t border-white/5 flex justify-between text-xl font-bold">
                        <span>Total Outflow</span>
                        <span class="text-white">TZS <?php echo number_format($total_expenses); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center text-[10px] text-slate-600 mt-12 uppercase tracking-[0.3em]">
            Generated by Strong Bridge ERP Intelligence
        </p>
    </div>
</body>
</html>
