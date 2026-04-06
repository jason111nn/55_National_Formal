<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $hash = hash('sha256', $password);
    
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND password_hash = ?");
    $stmt->execute([$username, $hash]);
    $user = $stmt->fetch();
    
    if ($user) {
        // 若為 publisher role, 確認 publisher 狀態是否為 inactive
        if ($user['role'] === 'publisher') {
            $stmtP = $pdo->prepare("SELECT status FROM publishers WHERE id = ?");
            $stmtP->execute([$user['publisher_id']]);
            $pub = $stmtP->fetch();
            if (!$pub || $pub['status'] === 'inactive') {
                $error = "所屬出版社已被停用，無法登入。";
                $user = null;
            }
        }
        
        if ($user) {
            $_SESSION['user'] = $user;
            header("Location: /00_module_d/books");
            exit;
        }
    } else {
        $error = "無效的帳號或密碼";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>登入管理</title><style>body{font-family:sans-serif;margin:40px;}</style></head>
<body>
    <h1>登入</h1>
    <?php if($error) echo "<p style='color:red'>$error</p>"; ?>
    <form method="post">
        <div>帳號: <input type="text" name="username" required></div><br>
        <div>密碼: <input type="password" name="password" required></div><br>
        <button type="submit">登入</button>
    </form>
</body>
</html>
