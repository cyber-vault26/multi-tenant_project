<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
require 'db.php';
date_default_timezone_set('Africa/Nairobi'); 

// Same fallback chain as db.php: env vars (Railway-style) first,
// then config.local.php (cPanel/other hosts), no hardcoded secrets.
$smtpHost = getenv('SMTP_HOST');
$smtpUser = getenv('SMTP_USER');
$smtpPass = getenv('SMTP_PASS');
$smtpPort = getenv('SMTP_PORT') ?: 587;
$fromEmail = getenv('SMTP_FROM_EMAIL');
$fromName = getenv('SMTP_FROM_NAME') ?: 'Strong Bridge Support';
$baseUrl = getenv('APP_BASE_URL');

if (!$smtpUser && file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
    if (defined('SMTP_USER')) {
        $smtpHost = SMTP_HOST;
        $smtpUser = SMTP_USER;
        $smtpPass = SMTP_PASS;
        $smtpPort = defined('SMTP_PORT') ? SMTP_PORT : 587;
        $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : $smtpUser;
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : $fromName;
    }
    if (defined('APP_BASE_URL')) {
        $baseUrl = APP_BASE_URL;
    }
}

// Last resort: build the base URL from the actual request, so this
// works correctly on whatever domain it's actually running on,
// rather than a hardcoded value that breaks the moment you move hosts.
if (!$baseUrl) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);

        $resetLink = $baseUrl . "/reset-password.php?token=" . $token;

        if (!$smtpUser) {
            // No SMTP configured on this environment — fail loudly in
            // logs rather than silently pretending an email was sent.
            error_log("Password reset requested for $email but no SMTP credentials are configured (set SMTP_USER/SMTP_PASS via env vars or config.local.php).");
            header("Location: forgot-password.php?msg=sent");
            exit();
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpPort;

            
            $mail->setFrom($fromEmail ?: $smtpUser, $fromName);
            $mail->addAddress($email);

            
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
                <div style='font-family: sans-serif; padding: 20px; border: 1px solid #eee;'>
                    <h2>Reset Your Password</h2>
                    <p>Umeomba kubadilisha password yako kwenye Strong Bridge ERP.</p>
                    <p>Bonyeza kitufe hapa chini ili kuweka password mpya. Link hii itaisha muda wake baada ya saa 1.</p>
                    <a href='$resetLink' style='display: inline-block; padding: 10px 20px; background-color: #0ea5e9; color: white; text-decoration: none; border-radius: 8px;'>Reset Password</a>
                    <p>Kama hukuomba mabadiliko haya, tafadhali puuza email hii.</p>
                </div>";

            $mail->send();
            header("Location: forgot-password.php?msg=sent");
        } catch (Exception $e) {
            echo "Email ishindwa kutumwa. Error: {$mail->ErrorInfo}";
        }
    } else {
      
        header("Location: forgot-password.php?msg=sent");
    }
}
