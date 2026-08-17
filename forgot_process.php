<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 
require 'db.php';
date_default_timezone_set('Africa/Nairobi'); 

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

        $resetLink = "http://localhost:8000/reset-password.php?token=" . $token;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';                     
            $mail->SMTPAuth   = true;
            $mail->Username   = 'malalelufungulo@gmail.com';              
            $mail->Password   = 'bzwl vxcu agnx izrk';                
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            
            $mail->setFrom('no-reply@strongbridge.com', 'Strong Bridge Support');
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
