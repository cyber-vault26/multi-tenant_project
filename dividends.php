<?php
require 'db.php';
require 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$tenant_id = $_SESSION['tenant_id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$isManager = in_array($role, ['admin', 'manager', 'super_admin'], true);

if (!$isManager) {
    die("You are not allowed to manage dividends.");
}

// ---------------------------------------------------------------
// Declare a new dividend
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'declare') {
    $fiscalYear = trim($_POST['fiscal_year']);
    $ratePercent = (float) $_POST['rate_percent'];
    $basis = $_POST['basis'] === 'savings' ? 'savings' : 'shares';
    $declaredDate = $_POST['declared_date'] ?: date('Y-m-d');

    if ($ratePercent <= 0) {
        die("Rate must be greater than zero.");
    }

    try {
        $pdo->beginTransaction();

        // Snapshot every active member's current balance on the chosen basis,
        // right now, at declaration time — this is what the dividend is computed on.
        if ($basis === 'shares') {
            $balanceQuery = "
                SELECT m.id, COALESCE(SUM(CASE WHEN st.type='purchase' THEN st.amount ELSE -st.amount END), 0) AS basis_amount
                FROM sacco_members m
                LEFT JOIN share_transactions st ON st.member_id = m.id
                WHERE m.tenant_id = ? AND m.status = 'active'
                GROUP BY m.id
                HAVING basis_amount > 0
            ";
        } else {
            $balanceQuery = "
                SELECT m.id, COALESCE(SUM(CASE WHEN sv.type='deposit' THEN sv.amount ELSE -sv.amount END), 0) AS basis_amount
                FROM sacco_members m
                LEFT JOIN savings_transactions sv ON sv.member_id = m.id
                WHERE m.tenant_id = ? AND m.status = 'active'
                GROUP BY m.id
                HAVING basis_amount > 0
            ";
        }

        $stmt = $pdo->prepare($balanceQuery);
        $stmt->execute([$tenant_id]);
        $memberBalances = $stmt->fetchAll();

        $totalPayout = 0;
        foreach ($memberBalances as $mb) {
            $totalPayout += $mb['basis_amount'] * ($ratePercent / 100);
        }

        $stmt = $pdo->prepare("INSERT INTO dividend_declarations (tenant_id, fiscal_year, rate_percent, basis, declared_date, total_payout_amount, declared_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $fiscalYear, $ratePercent, $basis, $declaredDate, $totalPayout, $user_id]);
        $declarationId = $pdo->lastInsertId();

        $payoutStmt = $pdo->prepare("INSERT INTO dividend_payouts (tenant_id, declaration_id, member_id, basis_amount, payout_amount, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        foreach ($memberBalances as $mb) {
            $payoutAmount = round($mb['basis_amount'] * ($ratePercent / 100), 2);
            $payoutStmt->execute([$tenant_id, $declarationId, $mb['id'], $mb['basis_amount'], $payoutAmount]);
        }

        $pdo->commit();
        logAction($pdo, "Dividend Declared", "$fiscalYear at {$ratePercent}% on $basis — TZS " . number_format($totalPayout) . " across " . count($memberBalances) . " members");

        header("Location: dividends.php?msg=declared");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// ---------------------------------------------------------------
// Mark a single payout as paid — debits the payout from Cash/Bank
// and posts it as a real expense, same accounting pattern as the
// shares/savings module.
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay') {
    $payoutId = (int) $_POST['payout_id'];

    $stmt = $pdo->prepare("
        SELECT p.*, m.full_name FROM dividend_payouts p
        JOIN sacco_members m ON m.id = p.member_id
        WHERE p.id = ? AND p.tenant_id = ?
    ");
    $stmt->execute([$payoutId, $tenant_id]);
    $payout = $stmt->fetch();

    if (!$payout) { die("Payout not found."); }
    if ($payout['status'] === 'paid') { die("This payout has already been marked paid."); }

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE dividend_payouts SET status = 'paid', paid_date = ? WHERE id = ?")
            ->execute([date('Y-m-d'), $payoutId]);

        $pdo->prepare("UPDATE accounts SET balance = balance - ? WHERE account_name = 'Cash on Hand' AND tenant_id = ?")
            ->execute([$payout['payout_amount'], $tenant_id]);
        $pdo->prepare("UPDATE accounts SET balance = balance + ? WHERE account_name = 'Dividends Paid' AND tenant_id = ?")
            ->execute([$payout['payout_amount'], $tenant_id]);

        $pdo->prepare("INSERT INTO journal_entries (tenant_id, account_id, description, debit, credit)
                        VALUES (?, (SELECT id FROM accounts WHERE account_name = 'Dividends Paid' AND tenant_id = ? LIMIT 1), ?, ?, 0)")
            ->execute([$tenant_id, $tenant_id, "Dividend paid — " . $payout['full_name'], $payout['payout_amount']]);

        $pdo->commit();
        logAction($pdo, "Dividend Paid", "TZS " . number_format($payout['payout_amount']) . " to " . $payout['full_name']);

        header("Location: dividends.php?msg=paid");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// ---------------------------------------------------------------
// Data for display
// ---------------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM dividend_declarations WHERE tenant_id = ? ORDER BY declared_date DESC, id DESC");
$stmt->execute([$tenant_id]);
$declarations = $stmt->fetchAll();

$selectedDeclarationId = (int) ($_GET['declaration_id'] ?? ($declarations[0]['id'] ?? 0));
$payouts = [];
if ($selectedDeclarationId) {
    $stmt = $pdo->prepare("
        SELECT p.*, m.full_name, m.member_number
        FROM dividend_payouts p
        JOIN sacco_members m ON m.id = p.member_id
        WHERE p.declaration_id = ? AND p.tenant_id = ?
        ORDER BY p.payout_amount DESC
    ");
    $stmt->execute([$selectedDeclarationId, $tenant_id]);
    $payouts = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dividends — Strong Bridge</title>
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
        <header class="mb-8">
            <h1 class="text-2xl font-bold text-white">Dividends</h1>
            <p class="text-sm text-slate-500">Declare annual dividends and manage member payouts.</p>
        </header>

        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 text-emerald-400 text-sm font-medium">
                <?php echo $_GET['msg'] === 'declared' ? 'Dividend declared and payouts calculated.' : 'Payout marked as paid.'; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- DECLARE FORM -->
            <div class="glass-card rounded-[2rem] p-8 lg:col-span-1 h-fit">
                <h2 class="text-lg font-bold mb-6">Declare New Dividend</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="declare">
                    <input type="text" name="fiscal_year" placeholder="Fiscal Year (e.g. 2026)" required class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <input type="number" step="0.01" name="rate_percent" placeholder="Rate (%)" required min="0.01" class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <select name="basis" class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                        <option value="shares">Based on Share Capital</option>
                        <option value="savings">Based on Savings Balance</option>
                    </select>
                    <input type="date" name="declared_date" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-900 border border-white/10 p-3 rounded-xl text-sm outline-none">
                    <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 py-3 rounded-xl font-bold text-sm">Calculate & Declare</button>
                    <p class="text-xs text-slate-600">This snapshots every active member's current balance right now and calculates their payout at the given rate. It doesn't move any money yet — you mark individual payouts as paid afterward.</p>
                </form>
            </div>

            <!-- DECLARATIONS + PAYOUTS -->
            <div class="lg:col-span-2 space-y-6">
                <div class="glass-card rounded-[2rem] p-6">
                    <h2 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4">Past Declarations</h2>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($declarations as $d): ?>
                        <a href="dividends.php?declaration_id=<?php echo $d['id']; ?>"
                           class="px-4 py-2 rounded-xl text-sm font-bold border <?php echo $d['id'] == $selectedDeclarationId ? 'bg-sky-500 border-sky-500 text-white' : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10'; ?>">
                            <?php echo htmlspecialchars($d['fiscal_year']); ?> — <?php echo $d['rate_percent']; ?>%
                        </a>
                        <?php endforeach; ?>
                        <?php if (empty($declarations)): ?><p class="text-slate-600 italic text-sm">No dividends declared yet.</p><?php endif; ?>
                    </div>
                </div>

                <?php if ($selectedDeclarationId): ?>
                <div class="glass-card rounded-[2rem] overflow-hidden">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead class="bg-white/5 text-slate-500 uppercase text-[10px] tracking-widest">
                            <tr>
                                <th class="p-4">Member</th>
                                <th class="p-4">Basis Amount</th>
                                <th class="p-4">Payout</th>
                                <th class="p-4">Status</th>
                                <th class="p-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payouts as $p): ?>
                            <tr class="border-t border-white/5">
                                <td class="p-4 font-bold text-white"><?php echo htmlspecialchars($p['full_name']); ?></td>
                                <td class="p-4 text-slate-400">TZS <?php echo number_format($p['basis_amount']); ?></td>
                                <td class="p-4 font-bold text-emerald-400">TZS <?php echo number_format($p['payout_amount']); ?></td>
                                <td class="p-4">
                                    <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase <?php echo $p['status'] === 'paid' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'; ?>">
                                        <?php echo htmlspecialchars($p['status']); ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <?php if ($p['status'] === 'pending'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="pay">
                                        <input type="hidden" name="payout_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="text-sky-400 hover:underline text-xs font-bold">Mark Paid</button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
