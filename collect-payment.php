<?php
require 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];

// 1. get active loans and names of customer
$stmt = $pdo->prepare("
    SELECT loans.id, clients.full_name, loans.amount 
    FROM loans 
    JOIN clients ON loans.client_id = clients.id 
    WHERE loans.tenant_id = ? AND loans.status = 'active'
");
$stmt->execute([$tenant_id]);
$active_loans = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = $_POST['loan_id'];
    $amount_paid = $_POST['amount_paid'];
    $user_id = $_SESSION['user_id'];
    $tenant_id = $_SESSION['tenant_id'];

    try {
        $pdo->beginTransaction();


        $stmt = $pdo->prepare("INSERT INTO repayments (tenant_id, loan_id, amount_paid, collected_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $loan_id, $amount_paid, $user_id]);

        $stmt = $pdo->prepare("SELECT id, total_due FROM loan_schedules WHERE loan_id = ? AND status = 'pending' ORDER BY installment_no ASC LIMIT 1");
        $stmt->execute([$loan_id]);
        $next_installment = $stmt->fetch();

        if ($next_installment) {
            if ($amount_paid >= $next_installment['total_due']) {
                $update_sched = $pdo->prepare("UPDATE loan_schedules SET status = 'paid' WHERE id = ?");
                $update_sched->execute([$next_installment['id']]);
            }
        }

        $pdo->commit();
        header("Location: dashboard.php?msg=payment_received");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Payment — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }</style>
</head>
<body class="p-8 flex justify-center items-center min-h-screen">
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
    <div class="glass w-full max-w-md p-10 rounded-[2.5rem]">
        <h2 class="text-2xl font-bold">Receive a refund(Payment)</h2>
        <form method="POST" class="mt-8 space-y-6">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Choose Customer loan</label>
                <select name="loan_id" required class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl mt-2 outline-none focus:ring-2 focus:ring-sky-500 text-sm">
                    <option value="">-- Choose Loan --</option>
                    <?php foreach($active_loans as $l): ?>
                        <option value="<?php echo $l['id']; ?>"><?php echo $l['full_name']; ?> (Debt: <?php echo number_format($l['amount']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Amount paid (TZS)</label>
                <input type="number" name="amount_paid" required placeholder="example: 5000" class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl mt-2 outline-none focus:ring-2 focus:ring-sky-500">
            </div>

            <button type="submit" class="w-full bg-emerald-600 py-4 rounded-xl font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-500/20">
                Save payment
            </button>
            <a href="dashboard.php" class="block text-center text-sm text-slate-500 mt-4">Reject</a>
        </form>
    </div>
</body>
</html>
