<?php
require 'db.php';
session_start();

$loan_id = $_GET['id'];
$tenant_id = $_SESSION['tenant_id'];

// 1. get loan and client info 
$stmt = $pdo->prepare("
    SELECT loans.*, clients.full_name, clients.phone_number 
    FROM loans 
    JOIN clients ON loans.client_id = clients.id 
    WHERE loans.id = ? AND loans.tenant_id = ?
");
$stmt->execute([$loan_id, $tenant_id]);
$loan = $stmt->fetch();

// 2. get a history of payment
$stmt = $pdo->prepare("SELECT * FROM repayments WHERE loan_id = ? ORDER BY payment_date DESC");
$stmt->execute([$loan_id]);
$repayments = $stmt->fetchAll();

// 3. calculation of ERP
$principal = $loan['amount'];
$interest_total = $principal * ($loan['interest_rate'] / 100);
$total_to_pay = $principal + $interest_total;

$total_paid = 0;
foreach($repayments as $r) { $total_paid += $r['amount_paid']; }

$balance = $total_to_pay - $total_paid;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Details — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }</style>
</head>
<body class="p-8 lg:ml-64 bg-slate-950 min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <div class="max-w-4xl mx-auto">
        <header class="mb-10">
            <h1 class="text-3xl font-bold">Loan Explanation</h1>
            <p class="text-slate-500">Customer: <?php echo $loan['full_name']; ?> | <?php echo $loan['phone_number']; ?></p>
        </header>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="glass p-6 rounded-3xl">
                <p class="text-xs text-slate-500 uppercase font-bold">Total debt</p>
                <h3 class="text-xl font-bold mt-2 text-white">TZS <?php echo number_format($total_to_pay); ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl">
                <p class="text-xs text-slate-500 uppercase font-bold text-emerald-400">Total Amount paid</p>
                <h3 class="text-xl font-bold mt-2 text-emerald-400">TZS <?php echo number_format($total_paid); ?></h3>
            </div>
            <div class="glass p-6 rounded-3xl border-l-4 border-sky-500">
                <p class="text-xs text-slate-500 uppercase font-bold text-sky-400">Balance</p>
                <h3 class="text-xl font-bold mt-2 text-sky-400">TZS <?php echo number_format($balance); ?></h3>
            </div>
        </div>

        <div class="glass rounded-3xl overflow-hidden">
	    <div class="p-6 border-b border-white/5 font-bold">History of restoration</div>
            <?php
$stmt = $pdo->prepare("SELECT * FROM loan_schedules WHERE loan_id = ? ORDER BY installment_no ASC");
$stmt->execute([$loan_id]);
$schedules = $stmt->fetchAll();
?>

<div class="mt-10 glass p-8 rounded-[2.5rem]">
    <h3 class="text-xl font-bold mb-6 flex items-center gap-2">
        <svg class="w-5 h-5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
        Repayment Schedule (Mpango wa Malipo)
    </h3>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="text-slate-500 uppercase text-[10px] tracking-widest">
                <tr>
                    <th class="p-4">No.</th>
                    <th class="p-4">Due Date</th>
                    <th class="p-4">Principal</th>
                    <th class="p-4">Interest</th>
                    <th class="p-4">Total Installment</th>
                    <th class="p-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($schedules as $s): ?>
                <tr class="border-t border-white/5 hover:bg-white/5 transition-colors">
                    <td class="p-4 text-slate-500"><?php echo $s['installment_no']; ?></td>
                    <td class="p-4 font-medium text-white"><?php echo date('d M, Y', strtotime($s['due_date'])); ?></td>
                    <td class="p-4 italic"><?php echo number_format($s['principal_amount']); ?></td>
                    <td class="p-4 italic"><?php echo number_format($s['interest_amount']); ?></td>
                    <td class="p-4 font-bold text-sky-400"><?php echo number_format($s['total_due']); ?></td>
                    <td class="p-4">
                        <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase 
                            <?php echo $s['status'] == 'paid' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500'; ?>">
                            <?php echo $s['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
            <table class="w-full text-left text-sm">
                <thead class="bg-white/5 text-slate-500 uppercase text-[10px]">
                    <tr>
                        <th class="p-5">Date</th>
                        <th class="p-5">Amount paid</th>
                        <th class="p-5">Collected by</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($repayments as $r): ?>
                    <tr class="border-t border-white/5">
                        <td class="p-5"><?php echo date('d M, Y H:i', strtotime($r['payment_date'])); ?></td>
                        <td class="p-5 font-bold text-emerald-400">TZS <?php echo number_format($r['amount_paid']); ?></td>
                        <td class="p-5 text-slate-500">Admin</td>
		    </tr>

                   <tr>
                        <td class="p-5"><?php echo date('d M, Y', strtotime($r['payment_date'])); ?></td>
                        <td class="p-5 font-bold text-emerald-400">TZS <?php echo number_format($r['amount_paid']); ?></td>
                        <td class="p-5">
                             <a href="generate-receipt.php?id=<?php echo $r['id']; ?>" target="_blank" class="text-sky-400 hover:underline">
                         📄 Print Receipt
                        </a>
                       </td>
                   </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
