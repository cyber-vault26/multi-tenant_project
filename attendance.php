<?php
require 'db.php';
require 'includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$tenant_id = $_SESSION['tenant_id'];
$user_id   = $_SESSION['user_id'];
$role      = $_SESSION['role'];
$isManager = in_array($role, ['admin', 'manager', 'super_admin'], true);
$today     = date('Y-m-d');

// ---------------------------------------------------------------
// Handle actions
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'checkin') {
        // One row per user per day. If it doesn't exist yet, create it;
        // if it exists but has no check_in, fill it in; if already
        // checked in, leave it alone (don't overwrite the real time).
        $stmt = $pdo->prepare("SELECT id, check_in FROM attendance WHERE user_id = ? AND work_date = ?");
        $stmt->execute([$user_id, $today]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $pdo->prepare("INSERT INTO attendance (tenant_id, user_id, work_date, check_in, status, source) VALUES (?, ?, ?, NOW(), 'present', 'manual')")
                ->execute([$tenant_id, $user_id, $today]);
            logAction($pdo, "Checked In", "Checked in for " . $today);
        } elseif (!$existing['check_in']) {
            $pdo->prepare("UPDATE attendance SET check_in = NOW(), status = 'present' WHERE id = ?")
                ->execute([$existing['id']]);
            logAction($pdo, "Checked In", "Checked in for " . $today);
        }
        header("Location: attendance.php?msg=checked_in");
        exit();
    }

    if ($action === 'checkout') {
        $stmt = $pdo->prepare("SELECT id, check_in, check_out FROM attendance WHERE user_id = ? AND work_date = ?");
        $stmt->execute([$user_id, $today]);
        $existing = $stmt->fetch();

        if ($existing && $existing['check_in'] && !$existing['check_out']) {
            $pdo->prepare("UPDATE attendance SET check_out = NOW() WHERE id = ?")->execute([$existing['id']]);
            logAction($pdo, "Checked Out", "Checked out for " . $today);
        }
        header("Location: attendance.php?msg=checked_out");
        exit();
    }

    // Admin/manager manually setting someone's status (e.g. marking
    // absent or on_leave for a day they never checked in themselves)
    if ($action === 'mark' && $isManager) {
        $target_user_id = (int) $_POST['target_user_id'];
        $mark_date = $_POST['mark_date'] ?? $today;
        $status = $_POST['status'] ?? 'present';
        $notes = trim($_POST['notes'] ?? '') ?: null;

        // Confirm the target belongs to the same tenant before touching anything
        $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND tenant_id = ?");
        $check->execute([$target_user_id, $tenant_id]);
        if ($check->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO attendance (tenant_id, user_id, work_date, status, notes, source)
                VALUES (?, ?, ?, ?, ?, 'manual')
                ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes)
            ");
            $stmt->execute([$tenant_id, $target_user_id, $mark_date, $status, $notes]);
            logAction($pdo, "Attendance Marked", "Set attendance status to '$status' for user #$target_user_id on $mark_date");
        }
        header("Location: attendance.php?date=" . urlencode($mark_date) . "&msg=marked");
        exit();
    }
}

// ---------------------------------------------------------------
// Data for display
// ---------------------------------------------------------------

// My own attendance status for today
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? AND work_date = ?");
$stmt->execute([$user_id, $today]);
$myToday = $stmt->fetch();

// My last 14 days
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE user_id = ? ORDER BY work_date DESC LIMIT 14");
$stmt->execute([$user_id]);
$myHistory = $stmt->fetchAll();

// Manager/admin: whole-team view for a selected date (default today)
$viewDate = $_GET['date'] ?? $today;
$teamRows = [];
if ($isManager) {
    $stmt = $pdo->prepare("
        SELECT u.id AS user_id, u.full_name, u.department, u.position_title,
               a.check_in, a.check_out, a.status, a.notes
        FROM users u
        LEFT JOIN attendance a ON a.user_id = u.id AND a.work_date = ?
        WHERE u.tenant_id = ?
        ORDER BY u.full_name ASC
    ");
    $stmt->execute([$viewDate, $tenant_id]);
    $teamRows = $stmt->fetchAll();
}

$statusColors = [
    'present'  => 'bg-emerald-500/10 text-emerald-400',
    'late'     => 'bg-amber-500/10 text-amber-400',
    'absent'   => 'bg-red-500/10 text-red-400',
    'on_leave' => 'bg-indigo-500/10 text-indigo-400',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance — Strong Bridge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #020617; color: white; font-family: 'Inter', sans-serif; }
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
        <header class="mb-10">
            <h1 class="text-3xl font-bold">Attendance</h1>
            <p class="text-slate-500 text-sm">Check in, check out, and track team attendance.</p>
        </header>

        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 text-emerald-400 text-sm font-medium">
                <?php
                    $msgs = ['checked_in' => 'Checked in successfully.', 'checked_out' => 'Checked out successfully.', 'marked' => 'Attendance status updated.'];
                    echo htmlspecialchars($msgs[$_GET['msg']] ?? '');
                ?>
            </div>
        <?php endif; ?>

        <!-- MY TODAY -->
        <div class="glass-card rounded-[2rem] p-8 mb-8">
            <h2 class="text-lg font-bold mb-1">Today — <?php echo date('D, M j Y'); ?></h2>
            <p class="text-slate-500 text-sm mb-6">Your own attendance for today.</p>

            <div class="flex flex-wrap items-center gap-6">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Check-in</p>
                    <p class="text-xl font-bold"><?php echo $myToday && $myToday['check_in'] ? date('H:i', strtotime($myToday['check_in'])) : '—'; ?></p>
                </div>
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Check-out</p>
                    <p class="text-xl font-bold"><?php echo $myToday && $myToday['check_out'] ? date('H:i', strtotime($myToday['check_out'])) : '—'; ?></p>
                </div>

                <div class="flex-1"></div>

                <?php if (!$myToday || !$myToday['check_in']): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="checkin">
                        <button type="submit" class="bg-sky-500 hover:bg-sky-600 px-8 py-4 rounded-xl font-bold text-sm transition-all">Check In</button>
                    </form>
                <?php elseif (!$myToday['check_out']): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="bg-red-500/90 hover:bg-red-500 px-8 py-4 rounded-xl font-bold text-sm transition-all">Check Out</button>
                    </form>
                <?php else: ?>
                    <span class="text-emerald-400 text-sm font-bold">Day complete ✓</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isManager): ?>
            <!-- TEAM OVERVIEW -->
            <div class="glass-card rounded-[2rem] p-8 mb-8">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <h2 class="text-lg font-bold">Team Attendance</h2>
                    <form method="GET" class="flex items-center gap-3">
                        <input type="date" name="date" value="<?php echo htmlspecialchars($viewDate); ?>"
                               class="bg-slate-900 border border-white/10 px-4 py-2 rounded-lg text-sm outline-none"
                               onchange="this.form.submit()">
                    </form>
                </div>

                <table class="w-full text-left text-sm border-collapse">
                    <thead class="bg-white/5 text-slate-500 uppercase text-[10px] tracking-widest">
                        <tr>
                            <th class="p-4">Name</th>
                            <th class="p-4">Department</th>
                            <th class="p-4">Check-in</th>
                            <th class="p-4">Check-out</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Mark</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamRows as $r): ?>
                        <tr class="border-t border-white/5">
                            <td class="p-4 font-bold text-white"><?php echo htmlspecialchars($r['full_name']); ?></td>
                            <td class="p-4 text-slate-400"><?php echo htmlspecialchars($r['department'] ?: '—'); ?></td>
                            <td class="p-4 text-slate-400"><?php echo $r['check_in'] ? date('H:i', strtotime($r['check_in'])) : '—'; ?></td>
                            <td class="p-4 text-slate-400"><?php echo $r['check_out'] ? date('H:i', strtotime($r['check_out'])) : '—'; ?></td>
                            <td class="p-4">
                                <?php $st = $r['status'] ?? null; ?>
                                <?php if ($st): ?>
                                    <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase <?php echo $statusColors[$st] ?? 'bg-slate-500/10 text-slate-400'; ?>">
                                        <?php echo htmlspecialchars($st); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase bg-slate-500/10 text-slate-500">no record</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <form method="POST" class="flex items-center gap-2">
                                    <input type="hidden" name="action" value="mark">
                                    <input type="hidden" name="target_user_id" value="<?php echo (int) $r['user_id']; ?>">
                                    <input type="hidden" name="mark_date" value="<?php echo htmlspecialchars($viewDate); ?>">
                                    <select name="status" class="bg-slate-900 border border-white/10 rounded-lg text-xs px-2 py-1.5 outline-none">
                                        <option value="present">Present</option>
                                        <option value="late">Late</option>
                                        <option value="absent">Absent</option>
                                        <option value="on_leave">On Leave</option>
                                    </select>
                                    <button type="submit" class="text-sky-400 hover:underline text-xs font-bold">Set</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- MY HISTORY -->
        <div class="glass-card rounded-[2rem] p-8">
            <h2 class="text-lg font-bold mb-6">My Recent History</h2>
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-white/5 text-slate-500 uppercase text-[10px] tracking-widest">
                    <tr>
                        <th class="p-4">Date</th>
                        <th class="p-4">Check-in</th>
                        <th class="p-4">Check-out</th>
                        <th class="p-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myHistory as $h): ?>
                    <tr class="border-t border-white/5">
                        <td class="p-4 text-slate-300"><?php echo date('M j, Y', strtotime($h['work_date'])); ?></td>
                        <td class="p-4 text-slate-400"><?php echo $h['check_in'] ? date('H:i', strtotime($h['check_in'])) : '—'; ?></td>
                        <td class="p-4 text-slate-400"><?php echo $h['check_out'] ? date('H:i', strtotime($h['check_out'])) : '—'; ?></td>
                        <td class="p-4">
                            <span class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase <?php echo $statusColors[$h['status']] ?? 'bg-slate-500/10 text-slate-400'; ?>">
                                <?php echo htmlspecialchars($h['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($myHistory)): ?>
                    <tr><td colspan="4" class="p-6 text-center text-slate-600 italic">No attendance records yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
