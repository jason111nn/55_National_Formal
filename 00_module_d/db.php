<?php
session_start();
$host = '127.0.0.1';
$db   = '55_national';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Check logged in user
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// Check auth
function requireAuth($allowedRoles = ['super', 'publisher']) {
    $user = getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo "<script>alert('401 Unauthorized'); window.location.href='/00_module_d/login';</script>";
        exit;
    }
    if (!in_array($user['role'], $allowedRoles)) {
        http_response_code(403);
        echo "403 Forbidden";
        exit;
    }
    return $user;
}

function calculateIsbnCheckDigit($isbn12) {
    // 假設 isbn12 是只有數字的字串
    $isbn12 = str_replace('-', '', $isbn12);
    if(strlen($isbn12) !== 12) return false;
    $sum = 0;
    for ($i=0; $i<12; $i++) {
        $weight = ($i % 2 === 0) ? 1 : 3;
        $sum += (int)$isbn12[$i] * $weight;
    }
    $rem = $sum % 10;
    return $rem === 0 ? 0 : 10 - $rem;
}
?>
