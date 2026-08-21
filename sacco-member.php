<?php
require 'db.php';
require 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];
$user_id = $_SESSION['user_id'];

$member_id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM sacco_members WHERE id = ? AND tenant_id = ?");
$stmt->execute([$member_id, $tenant_id]);
$member = $stmt->fetch();

if (!$member) {
    die("Member not found.");
}

// ---------------------------------------------------------------
// Handle transactions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';
    $amount = (float) ($_POST['amount'] ?? 0);
    $txDate = $_POST['transaction_date'] ?: date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '') ?: null;

    if ($amount <= 0) {
        die("Amount must be greater than zero.");
    }

    try {
        if ($formType === 'share_purchase' || $formType === 'share_withdrawal') {
            $type = $formType === 'share_purchase' ? 'purchase' : 'withdrawal';
            $sharesCount = (int) ($_POST['shares_count'] ?? 0);

            if ($type === 'withdrawal') {
                // Enforce: can't withdraw more shares than the member currently holds.
                $bal = $pdo->prepare("
                    SELECT COALESCE(SUM(CASE WHEN type='purchase' THEN amount ELSE -amount END), 0) AS bal
                    FROM share_transactions WHERE member_id = ?
                ");
                $bal->execute([$member_id]);
                $currentBalance = (float) $bal->fetchColumn();
                if ($amount > $currentBalance) {
                    die("Error: Cannot withdraw more than the member's current share balance (TZS " . number_format($currentBalance) . ").");
                }
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO share_transactions (tenant_id, member_id, type, shares_count, amount, transaction_date, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenant_id, $member_id, $type, $sharesCount, $amount, $txDate, $notes, $user_id]);

            // Cash moves opposite to the liability/equity side:
            // purchase = cash IN, shares capital UP. withdrawal = cash OUT, shares capital DOWN.
            $cashDelta = $type === 'purchase' ? $amount : -$amount;
            $equityDelta = $type === 'purchase' ? $amount : -$amount;

            $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_name = 'Cash on Hand' AND tenant_id = ?")
                ->execute([$cashDelta, $tenant_id]);
            $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_name = 'Member Shares Capital' AND tenant_id = ?")
                ->execute([$equityDelta, $tenant_id]);

            $pdo->prepare("INSERT INTO journal_entries (tenant_id, account_id, description, debit, credit)
                            VALUES (?, (SELECT id FROM accounts WHERE account_name = 'Cash on Hand' AND tenant_id = ? LIMIT 1), ?, ?, ?)")
                ->execute([
                    $tenant_id, $tenant_id, ucfirst($type) . " of shares — " . $member['full_name'],
                    $type === 'purchase' ? $amount : 0,
                    $type === 'withdrawal' ? $amount : 0,
                ]);

            $pdo->commit();
            logAction($pdo, "SACCOS Share " . ucfirst($type), "$sharesCount shares (TZS " . number_format($amount) . ") for {$member['full_name']}");
        }

        if ($formType === 'savings_deposit' || $formType === 'savings_withdrawal') {
            $type = $formType === 'savings_deposit' ? 'deposit' : 'withdrawal';

            if ($type === 'withdrawal') {
                $bal = $pdo->prepare("
                    SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END), 0) AS bal
                    FROM savings_transactions WHERE member_id = ?
                ");
                $bal->execute([$member_id]);
                $currentBalance = (float) $bal->fetchColumn();
                if ($amount > $currentBalance) {
                    die("Error: Cannot withdraw more than the member's current savings balance (TZS " . number_format($currentBalance) . ").");
                }
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO savings_transactions (tenant_id, member_id, type, amount, transaction_date, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenant_id, $member_id, $type, $amount, $txDate, $notes, $user_id]);

            // deposit = cash IN, savings liability UP. withdrawal = cash OUT, savings liability DOWN.
            $cashDelta = $type === 'deposit' ? $amount : -$amount;
            $liabilityDelta = $type === 'deposit' ? $amount : -$amount;

            $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_name = 'Cash on Hand' AND tenant_id = ?")
                ->execute([$cashDelta, $tenant_id]);
            $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_name = 'Member Savings Deposits' AND tenant_id = ?")
                ->execute([$liabilityDelta, $tenant_id]);

            $pdo->prepare("INSERT INTO journal_entries (tenant_id, account_id, description, debit, credit)
                            VALUES (?, (SELECT id FROM accounts WHERE account_name = 'Cash on Hand' AND tenant_id = ? LIMIT 1), ?, ?, ?)")
                ->execute([
                    $tenant_id, $tenant_id, ucfirst($type) . " savings — " . $member['full_name'],
                    $type === 'deposit' ? $amount : 0,
                    $type === 'withdrawal' ? $amount : 0,
                ]);

            $pdo->commit();
            logAction($pdo, "SACCOS Savings " . ucfirst($type), "TZS " . number_format($amount) . " for {$member['full_name']}");
        }

        header("Location: sacco-member.php?id=$member_id&msg=recorded");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// ---------------------------------------------------------------
// Data for display
// ---------------------------------------------------------------
$stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type='purchase' THEN amount ELSE -amount END), 0) AS bal, COALESCE(SUM(CASE WHEN type='purchase' THEN shares_count ELSE -shares_count END), 0) AS cnt FROM share_transactions WHERE member_id = ?");
$stmt->execute([$member_id]);
$sharesSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN type='deposit' THEN amount ELSE -amount END), 0) AS bal FROM savings_transactions WHERE member_id = ?");
$stmt->execute([$member_id]);
$savingsSummary = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM share_transactions WHERE member_id = ? ORDER BY transaction_date DESC, id DESC LIMIT 20");
$stmt->execute([$member_id]);
$shareHistory = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM savings_transactions WHERE member_id = ? ORDER BY transaction_date DESC, id DESC LIMIT 20");
$stmt->execute([$member_id]);
$savingsHistory = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($member['full_name']); ?> — SACCOS — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; }
        .glass-card { background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-slate-200">
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

    <main class="lg:ml-64 p-8">
        <a href="sacco-members.php" class="text-sm text-slate-500 hover:text-white">← Back to Members</a>
        <header class="mb-8 mt-3">
            <h1 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($member['full_name']); ?></h1>
            <p class="text-sm text-slate-500">
                <?php echo htmlspecialchars($member['member_number'] ?: ('SM-' . str_pad($member['id'], 4, '0', STR_PAD_LEFT))); ?>
                · <?php echo htmlspecialchars($member['phone'] ?: 'No phone'); ?>
                · Joined <?php echo date('M d, Y', strtotime($member['join_date'])); ?>
            </p>
        </header>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'recorded'): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 text-emerald-400 text-sm font-medium">Transaction recorded successfully.</div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- SHARES -->
            <div class="glass-card rounded-[2rem] p-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Share Capital</p>
                        <p class="text-3xl font-bold mt-1">TZS <?php echo number_format($sharesSummary['bal']); ?></p>
                        <p class="text-xs text-slate-600 mt-1"><?php echo (int) $sharesSummary['cnt']; ?> shares held</p>
                    </div>
                </div>

                <form method="POST" class="grid grid-cols-2 gap-3 mb-6">
                    <input type="hidden" name="form_type" id="share_form_type" value="share_purchase">
                    <input type="number" name="shares_count" placeholder="No. of Shares" required min="1" class="col-span-2 bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <input type="number" step="0.01" name="amount" placeholder="Amount (TZS)" required min="0.01" class="bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" class="bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <input type="text" name="notes" placeholder="Notes (optional)" class="col-span-2 bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <button type="submit" onclick="document.getElementById('share_form_type').value='share_purchase'" class="bg-sky-500 hover:bg-sky-600 py-3 rounded-xl font-bold text-xs">+ Buy Shares</button>
                    <button type="submit" onclick="document.getElementById('share_form_type').value='share_withdrawal'" class="bg-white/5 border border-white/10 hover:bg-white/10 py-3 rounded-xl font-bold text-xs">− Withdraw Shares</button>
                </form>

                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Recent History</p>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <?php foreach ($shareHistory as $h): ?>
                    <div class="flex justify-between items-center text-sm border-t border-white/5 pt-2">
                        <div>
                            <span class="<?php echo $h['type'] === 'purchase' ? 'text-emerald-400' : 'text-red-400'; ?> font-bold">
                                <?php echo $h['type'] === 'purchase' ? '+' : '−'; ?>TZS <?php echo number_format($h['amount']); ?>
                            </span>
                            <span class="text-slate-600 text-xs ml-2"><?php echo (int) $h['shares_count']; ?> shares</span>
                        </div>
                        <span class="text-slate-600 text-xs"><?php echo date('M d', strtotime($h['transaction_date'])); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($shareHistory)): ?><p class="text-slate-600 italic text-sm">No share transactions yet.</p><?php endif; ?>
                </div>
            </div>

            <!-- SAVINGS -->
            <div class="glass-card rounded-[2rem] p-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Savings Balance</p>
                        <p class="text-3xl font-bold mt-1 text-emerald-400">TZS <?php echo number_format($savingsSummary['bal']); ?></p>
                    </div>
                </div>

                <form method="POST" class="grid grid-cols-2 gap-3 mb-6">
                    <input type="hidden" name="form_type" id="savings_form_type" value="savings_deposit">
                    <input type="number" step="0.01" name="amount" placeholder="Amount (TZS)" required min="0.01" class="col-span-2 bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" class="col-span-2 bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <input type="text" name="notes" placeholder="Notes (optional)" class="col-span-2 bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <button type="submit" onclick="document.getElementById('savings_form_type').value='savings_deposit'" class="bg-emerald-500 hover:bg-emerald-600 py-3 rounded-xl font-bold text-xs">+ Deposit</button>
                    <button type="submit" onclick="document.getElementById('savings_form_type').value='savings_withdrawal'" class="bg-white/5 border border-white/10 hover:bg-white/10 py-3 rounded-xl font-bold text-xs">− Withdraw</button>
                </form>

                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-2">Recent History</p>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <?php foreach ($savingsHistory as $h): ?>
                    <div class="flex justify-between items-center text-sm border-t border-white/5 pt-2">
                        <span class="<?php echo $h['type'] === 'deposit' ? 'text-emerald-400' : 'text-red-400'; ?> font-bold">
                            <?php echo $h['type'] === 'deposit' ? '+' : '−'; ?>TZS <?php echo number_format($h['amount']); ?>
                        </span>
                        <span class="text-slate-600 text-xs"><?php echo date('M d', strtotime($h['transaction_date'])); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($savingsHistory)): ?><p class="text-slate-600 italic text-sm">No savings transactions yet.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
