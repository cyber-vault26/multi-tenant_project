<?php
require 'db.php';
session_start();

if ($_SESSION['role'] !== 'admin') { die("You are not allowed to add employee!"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $tenant_id = $_SESSION['tenant_id'];

    try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, tenant_id, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fullName, $email, $password, $tenant_id, $role]);
        header("Location: staff.php?msg=added");
        exit();
    } catch (PDOException $e) {
        die("Error: Email tayari imesajiliwa.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; } .glass { background: rgba(15,23,42,0.6); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="glass w-full max-w-lg p-10 rounded-[2.5rem]">
        <h2 class="text-2xl font-bold mb-6 text-center">Register Employee</h2>
        <form method="POST" class="space-y-4">
            <input type="text" name="full_name" placeholder="Jina Kamili la Mfanyakazi" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none">
            <input type="email" name="email" placeholder="Work Email" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none">
            <input type="password" name="password" placeholder="Temporary Password" required class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none">
            
            <label class="text-xs font-bold text-slate-500 uppercase tracking-widest ml-1">Rank / Role</label>
            <select name="role" class="w-full bg-slate-900 border border-white/10 p-4 rounded-xl text-white outline-none">
                <option value="staff">Staff (Mauzo tu)</option>
                <option value="manager">Manager (Reports + Staff)</option>
                <option value="admin">Admin (All Access)</option>
            </select>
            
            <button type="submit" class="w-full bg-sky-500 py-4 rounded-xl font-bold">Create Staff Account</button>
            <a href="staff.php" class="block text-center text-sm text-slate-500 mt-4 underline">Cancel</a>
        </form>
    </div>
</body>
</html>
