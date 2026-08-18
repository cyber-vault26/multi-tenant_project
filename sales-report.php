<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];

//Total Revenue
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE tenant_id = ? AND DATE(sale_date) = CURDATE()");
$stmt->execute([$tenant_id]);
$today_revenue = $stmt->fetchColumn() ?? 0;

// 2. product sold today
$stmt = $pdo->prepare("SELECT SUM(quantity) FROM sales WHERE tenant_id = ? AND DATE(sale_date) = CURDATE()");
$stmt->execute([$tenant_id]);
$items_sold = $stmt->fetchColumn() ?? 0;

// Revenue - Cost
$stmt = $pdo->prepare("
    SELECT SUM(sales.quantity * products.purchase_price) as total_cost 
    FROM sales 
    JOIN products ON sales.product_id = products.id 
    WHERE sales.tenant_id = ? AND DATE(sales.sale_date) = CURDATE()
");
$stmt->execute([$tenant_id]);
$total_cost = $stmt->fetchColumn() ?? 0;

$net_profit = $today_revenue - $total_cost;

// Top Product
$stmt = $pdo->prepare("
    SELECT products.name, SUM(sales.quantity) as qty, SUM(sales.total_amount) as total 
    FROM sales 
    JOIN products ON sales.product_id = products.id 
    WHERE sales.tenant_id = ? AND DATE(sales.sale_date) = CURDATE()
    GROUP BY products.id 
    ORDER BY qty DESC LIMIT 5
");
$stmt->execute([$tenant_id]);
$top_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Report — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }</style>
</head>
<body class="text-slate-200">
    <?php include 'includes/sidebar.php'; ?>

    <main class="lg:ml-64 p-8">
        <header class="mb-10 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-bold text-white">Daily Sales Report</h1>
                <p class="text-slate-500">Today's sales report: <?php echo date('d M, Y'); ?></p>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-500 uppercase font-bold">Current Profitability</span>
                <h2 class="text-2xl font-bold text-emerald-400">TZS <?php echo number_format($net_profit); ?></h2>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="glass p-8 rounded-[2rem] border-l-4 border-sky-500">
                <p class="text-xs text-slate-500 uppercase font-bold">Today's Revenue</p>
                <h3 class="text-3xl font-bold mt-2 text-white">TZS <?php echo number_format($today_revenue); ?></h3>
            </div>
            <div class="glass p-8 rounded-[2rem]">
                <p class="text-xs text-slate-500 uppercase font-bold">Items Sold</p>
                <h3 class="text-3xl font-bold mt-2 text-white"><?php echo $items_sold; ?> <span class="text-sm font-normal text-slate-500">units</span></h3>
            </div>
            <div class="glass p-8 rounded-[2rem] border-l-4 border-emerald-500">
                <p class="text-xs text-slate-500 uppercase font-bold">Gross Profit</p>
                <h3 class="text-3xl font-bold mt-2 text-emerald-500">TZS <?php echo number_format($net_profit); ?></h3>
            </div>
        </div>

        <!-- Top Selling Products Table -->
        <div class="glass rounded-[2.5rem] overflow-hidden">
            <div class="p-6 border-b border-white/5">
                <h3 class="font-bold">Top Selling Products (Today)</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-white/5 text-slate-500 uppercase text-[10px]">
                    <tr>
                        <th class="p-5">Product Name</th>
                        <th class="p-5">Quantity Sold</th>
                        <th class="p-5">Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($top_products as $p): ?>
                    <tr class="border-t border-white/5">
                        <td class="p-5 font-bold"><?php echo $p['name']; ?></td>
                        <td class="p-5"><?php echo $p['qty']; ?> units</td>
                        <td class="p-5 text-sky-400 font-bold">TZS <?php echo number_format($p['total']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($top_products)): ?>
                        <tr><td colspan="3" class="p-10 text-center text-slate-500 italic">No sales have been made yet today.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
