<?php
require 'db.php';
require 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'staff') {
    header("Location: login.php"); exit();
}
$tenant_id = $_SESSION['tenant_id'];

$stmt = $pdo->prepare("SELECT id, account_name FROM accounts WHERE tenant_id = ? AND account_type = 'Expense'");
$stmt->execute([$tenant_id]);
$expense_accounts = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_id = $_POST['account_id'];
    $amount = $_POST['amount'];
    $desc = $_POST['description'];
    $date = $_POST['expense_date'];
    $user_id = $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO expenses (tenant_id, account_id, amount, description, expense_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $account_id, $amount, $desc, $date, $user_id]);

        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_name = 'Bank Account' AND tenant_id = ?");
        $stmt->execute([$amount, $tenant_id]);

        $stmt = $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $account_id]);

        $pdo->commit();
        logAction($pdo, "Expense Recorded", "Spent TZS " . number_format($amount) . " for " . $desc);
        $success = "Matumizi yamerekodiwa!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Hitilafu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }</style>
</head>
<body class="p-8 lg:ml-64 bg-slate-950 min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Expenses</h1>

        <?php if(isset($success)) echo "<div class='bg-emerald-500/10 text-emerald-400 p-4 rounded-xl mb-6'>$success</div>"; ?>

        <form method="POST" class="glass p-8 rounded-[2rem] space-y-5">
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Type of Use</label>
                <select name="account_id" required class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl mt-2 text-white outline-none">
                    <?php foreach($expense_accounts as $acc): ?>
                        <option value="<?php echo $acc['id']; ?>"><?php echo $acc['account_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Amount</label>
                <input type="number" name="amount" required class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl mt-2 text-white outline-none">
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Details</label>
                <textarea name="description" placeholder="Mfano: Malipo ya kodi ya pango" class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl mt-2 text-white outline-none"></textarea>
            </div>
            <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-widest">Date</label>
                <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl mt-2 text-white outline-none">
            </div>
            <button type="submit" class="w-full bg-sky-500 py-4 rounded-xl font-bold">Save Usage</button>
        </form>
    </div>
</body>
</html>
