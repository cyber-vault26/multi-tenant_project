<?php
require 'db.php';
require 'includes/functions.php';
session_start();

if (!isset($_SESSION['tenant_id']) || empty($_SESSION['tenant_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    // form uses "phone" as the input name, keep variable consistent
    $phoneNumber = trim($_POST['phone'] ?? $_POST['phone_number'] ?? '');
    $idNumber = trim($_POST['id_number']);
    
    $tenant_id = $_SESSION['tenant_id']; 

    try {
        $stmt = $pdo->prepare("INSERT INTO clients (tenant_id, full_name, phone_number, id_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $fullName, $phoneNumber, $idNumber]);

        // log after successful insert
        if (function_exists('logAction')) {
            logAction($pdo, "Client Registered", "Registered a new client: $fullName");
        }

        header("Location: clients.php");
        exit();
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { background-color: #020617; color: white; }</style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="bg-slate-900 border border-white/10 p-10 rounded-[2.5rem] w-full max-w-md">
        <h2 class="text-2xl font-bold">Register New Customer</h2>
        <form method="POST" class="mt-6 space-y-4">
            <input type="text" name="full_name" placeholder="Jina Kamili" required class="w-full bg-black/40 border border-white/10 p-4 rounded-xl text-white outline-none">
            <input type="text" name="phone" placeholder="Namba ya Simu" class="w-full bg-black/40 border border-white/10 p-4 rounded-xl text-white outline-none">
            <input type="text" name="id_number" placeholder="NIDA / Passport Number" class="w-full bg-black/40 border border-white/10 p-4 rounded-xl text-white outline-none">
            <button type="submit" class="w-full bg-sky-500 py-4 rounded-xl font-bold">Register the customer</button>
            <a href="clients.php" class="block text-center text-sm text-slate-500 mt-4">Reject</a>
        </form>
    </div>
</body>
</html>
