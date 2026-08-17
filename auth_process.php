<?php
require 'db.php';
session_start();
date_default_timezone_set('Africa/Nairobi');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? 'signin'; 
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($mode === 'signup') {
        $fullName = trim($_POST['full_name']); 
        $confirmPass = $_POST['confirm_password'];

        // 1. Uhakiki wa Password
        if ($password !== $confirmPass) {
            header("Location: login.php?error=pass_mismatch");
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT); 

        try {
            // Tunatumia tenant_id = 1 kama 'Lobby' ya kuanzia
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, tenant_id, role) VALUES (?, ?, ?, 1, 'staff')");
            $stmt->execute([$fullName, $email, $hashed_password]); 
            
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['full_name'] = $fullName;
            $_SESSION['tenant_id'] = 1; 
            $_SESSION['role'] = 'staff';

            header("Location: setup-org.php"); 
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                header("Location: login.php?error=email_exists");
            } else {
                die("Database Error: " . $e->getMessage());
            }
            exit();
        }
    } else {
        // LOGIN LOGIC
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['tenant_id'] = $user['tenant_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role']; 
            header("Location: dashboard.php");
        } else {
            header("Location: login.php?error=invalid");
        }
        exit();
    }
}