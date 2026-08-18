<?php
date_default_timezone_set('Africa/Nairobi'); 

$host     = getenv('MYSQLHOST');
$db_name  = getenv('MYSQLDATABASE');
$username = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$port     = getenv('MYSQLPORT') ?: '3306';
$charset  = 'utf8mb4';

if (!$host) {
    $host     = 'localhost';
    $db_name  = 'multi_tenant_system';
    $username = 'admin_erp';
    $password = 'kali';
    $port     = '3306';
}

$dsn = "mysql:host=$host;dbname=$db_name;port=$port;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,               
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
if (isset($_SESSION['tenant_id'])) {
    $stmt = $pdo->prepare("SELECT currency, name FROM tenants WHERE id = ?");
    $stmt->execute([$_SESSION['tenant_id']]);
    $global_tenant = $stmt->fetch();
    define('CURRENCY', $global_tenant['currency'] ?? 'TZS');
    define('BIZ_NAME', $global_tenant['name'] ?? 'Strong Bridge');
} else {
    define('CURRENCY', 'TZS');
    define('BIZ_NAME', 'Strong Bridge');
}
