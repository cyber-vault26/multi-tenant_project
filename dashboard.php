<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];

//  get the  Workspace/Kampuni
$stmt = $pdo->prepare("SELECT name FROM tenants WHERE id = ?");
$stmt->execute([$tenant_id]);
$org_name = $stmt->fetchColumn();

// get number of customer
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$total_clients = $stmt->fetchColumn();

// obtain Principal Amount
$stmt = $pdo->prepare("SELECT SUM(amount) FROM loans WHERE tenant_id = ?");
$stmt->execute([$tenant_id]);
$total_loan_amount = $stmt->fetchColumn() ?? 0;

// Obtain active Active loan
$stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE tenant_id = ? AND status = 'active'");
$stmt->execute([$tenant_id]);
$active_loans = $stmt->fetchColumn();

// obtain Recent Loans
$stmt = $pdo->prepare("
    SELECT loans.*, clients.full_name 
    FROM loans 
    JOIN clients ON loans.client_id = clients.id 
    WHERE loans.tenant_id = ? 
    ORDER BY loans.created_at DESC LIMIT 5
");
$stmt->execute([$tenant_id]);
$recent_loans = $stmt->fetchAll();

$daily_labels = [];
$sales_data = [];
$loan_collection_data = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $daily_labels[] = date('D', strtotime($date));

    $s = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE tenant_id = ? AND DATE(sale_date) = ?");
    $s->execute([$tenant_id, $date]);
    $sales_data[] = $s->fetchColumn() ?? 0;

    $r = $pdo->prepare("SELECT SUM(amount_paid) FROM repayments WHERE tenant_id = ? AND DATE(payment_date) = ?");
    $r->execute([$tenant_id, $date]);
    $loan_collection_data[] = $r->fetchColumn() ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — Strong Bridge ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="lg:ml-64 min-h-screen p-8">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-2xl font-bold text-white">Dashboard Overview</h1>
                <p class="text-sm text-slate-500 mt-1">Karibu tena, <?php echo explode(' ', $_SESSION['full_name'])[0]; ?>!</p>
            </div>
            
            <div class="flex gap-3">
                <div class="glass-card px-4 py-2 rounded-xl text-xs flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Workspace: <?php echo htmlspecialchars($org_name); ?>
                </div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Wateja -->
            <div class="glass-card p-6 rounded-[2rem]">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Clients</p>
                <h3 class="text-3xl font-bold text-white mt-2"><?php echo number_format($total_clients); ?></h3>
                <p class="text-[10px] text-sky-400 mt-2">Registered in your system</p>
            </div>

            <!-- Mikopo Active -->
            <div class="glass-card p-6 rounded-[2rem]">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Active Loans</p>
                <h3 class="text-3xl font-bold text-white mt-2"><?php echo number_format($active_loans); ?></h3>
                <p class="text-[10px] text-emerald-400 mt-2">Currently being repaid</p>
            </div>

            <!-- Jumla ya Pesa Iliyotolewa -->
            <div class="glass-card p-6 rounded-[2rem] lg:col-span-2">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Loan Portfolio</p>
                <h3 class="text-3xl font-bold text-sky-400 mt-2">TZS <?php echo number_format($total_loan_amount, 2); ?></h3>
                <p class="text-[10px] text-slate-500 mt-2">Principal amount issued across all clients</p>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="mt-10 glass-card rounded-[2.5rem] overflow-hidden">
            <div class="p-6 border-b border-white/5 flex justify-between items-center">
		<h3 class="font-bold">Recent Loan Activities</h3>
                <a href="loans.php" class="text-xs text-sky-400 hover:underline">View all loans</a>
            </div>
            <div class="p-0">
                <table class="w-full text-left text-sm">
                    <thead class="bg-white/5 text-slate-500 text-[10px] uppercase">
                        <tr>
                            <th class="p-5">Customer</th>
                            <th class="p-5">Amount</th>
                            <th class="p-5">Status</th>
                            <th class="p-5">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_loans as $loan): ?>
                        <tr class="border-t border-white/5">
                            <td class="p-5"><?php echo $loan['full_name']; ?></td>
                            <td class="p-5 font-bold">TZS <?php echo number_format($loan['amount']); ?></td>
                            <td class="p-5"><span class="text-[10px] bg-emerald-500/10 text-emerald-500 px-2 py-1 rounded-lg uppercase"><?php echo $loan['status']; ?></span></td>
			    <td class="p-5 text-slate-500"><?php echo date('d M, Y', strtotime($loan['created_at'])); ?></td>
                            <td class="p-5 text-sky-400">
                             <a href="loan-details.php?id=<?php echo $loan['id']; ?>" class="hover:underline font-bold">
                                     Manage
                              </a>
                        </tr>
                        <?php endforeach; ?>

                        <?php if(empty($recent_loans)): ?>
                        <tr>
                            <td colspan="4" class="p-10 text-center text-slate-500 italic">No loans have been issued yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
	    </div>
            <div class="mt-10 glass-card p-8 rounded-[2.5rem]">
               <h3 class="font-bold mb-6 text-white flex items-center gap-2">
               <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
        Financial Performance (Last 7 Days)
               </h3>
               <div class="h-64">
                   <canvas id="performanceChart"></canvas>
               </div>
            </div>      
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('performanceChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [
            {
                label: 'Sales Revenue',
                data: <?php echo json_encode($sales_data); ?>,
                borderColor: '#38bdf8',
                backgroundColor: 'rgba(56, 189, 248, 0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Loan Collections',
                data: <?php echo json_encode($loan_collection_data); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#94a3b8', font: { family: 'Inter' } } } },
        scales: {
            y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b' } },
            x: { grid: { display: false }, ticks: { color: '#64748b' } }
        }
    }
});
     </script>
</body>
</html>
