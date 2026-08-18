<?php
require 'db.php';
require 'includes/functions.php'; // Kwa ajili ya logAction
session_start();

if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit(); 
}

$tenant_id = $_SESSION['tenant_id'];

// Pata orodha ya wateja kwa ajili ya kuchagua
$stmt = $pdo->prepare("SELECT id, full_name FROM clients WHERE tenant_id = ? ORDER BY full_name ASC");
$stmt->execute([$tenant_id]);
$clients = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $amount = $_POST['amount'];
    $interest_rate = $_POST['interest_rate'];
    $duration = $_POST['duration'];
    $tenant_id = $_SESSION['tenant_id'];

    try {
        $pdo->beginTransaction();

        // 1. Rekodi Mkopo
        $stmt = $pdo->prepare("INSERT INTO loans (tenant_id, client_id, amount, interest_rate, duration_months, status) VALUES (?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$tenant_id, $client_id, $amount, $interest_rate, $duration]);
        $loan_id = $pdo->lastInsertId();

        // --- AMORTIZATION LOGIC (GENERATING SCHEDULE) ---
        $monthly_principal = $amount / $duration;
        $monthly_interest = ($amount * ($interest_rate / 100)) / $duration;
        $monthly_total = $monthly_principal + $monthly_interest;

        for ($i = 1; $i <= $duration; $i++) {
            $due_date = date('Y-m-d', strtotime("+$i months"));
            
            $sched_stmt = $pdo->prepare("INSERT INTO loan_schedules 
                (tenant_id, loan_id, installment_no, due_date, principal_amount, interest_amount, total_due) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $sched_stmt->execute([$tenant_id, $loan_id, $i, $due_date, $monthly_principal, $monthly_interest, $monthly_total]);
        }

        $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_name = 'Bank Account' AND tenant_id = ?")->execute([$amount, $tenant_id]);
        $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_name = 'Loan Receivables' AND tenant_id = ?")->execute([$amount, $tenant_id]);

        $pdo->commit();
        header("Location: loans.php?msg=loan_issued");
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
    <title>Issue Loan — Strong Bridge ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #020617; color: white; }
        .glass { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="p-8 flex justify-center items-center min-h-screen relative overflow-hidden">
    <!-- Background Gradient -->
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(56,189,248,0.08),transparent_50%)] pointer-events-none"></div>

    <div class="glass w-full max-w-lg p-10 rounded-[2.5rem] shadow-2xl relative z-10">
        <h2 class="text-3xl font-bold tracking-tight">Issue a new loan</h2>
        <p class="text-slate-400 text-sm mt-2 mb-8">Jaza vigezo vya mkopo ili kusasisha leja yako ya kifedha.</p>

        <form method="POST" class="mt-8 space-y-6">
            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Choose Borrower</label>
                <select name="client_id" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-2xl mt-2 outline-none focus:ring-2 focus:ring-sky-500 text-white transition-all">
                    <option value="">-- Select Customer --</option>
                    <?php foreach($clients as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Principal (TZS)</label>
                    <input type="number" name="amount" required placeholder="500,000" 
                        class="w-full bg-slate-900 border border-white/10 p-4 rounded-2xl mt-2 outline-none focus:ring-2 focus:ring-sky-500 text-white transition-all">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Interest Rate (%)</label>
                    <input type="number" step="0.1" name="interest_rate" required placeholder="10" 
                        class="w-full bg-slate-900 border border-white/10 p-4 rounded-2xl mt-2 outline-none focus:ring-2 focus:ring-sky-500 text-white transition-all">
                </div>
            </div>

            <div>
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Duration (Months)</label>
                <input type="number" name="duration" required placeholder="6" 
                    class="w-full bg-slate-900 border border-white/10 p-4 rounded-2xl mt-2 outline-none focus:ring-2 focus:ring-sky-500 text-white transition-all">
            </div>

            <button type="submit" class="w-full bg-sky-500 py-4 rounded-2xl font-bold text-sm shadow-lg shadow-sky-500/20 hover:bg-sky-600 hover:scale-[1.01] active:scale-[0.99] transition-all">
                Save and take out a loan
            </button>
            <a href="dashboard.php" class="block text-center text-sm text-slate-500 mt-4 hover:text-white transition-colors">Back</a>
        </form>
    </div>
</body>
</html>
