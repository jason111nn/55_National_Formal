<?php
session_start();

// 初始化 counters 陣列
if (!isset($_SESSION['counters'])) {
    $_SESSION['counters'] = [];
}

// 處理動作
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'add_counter') {
        $_SESSION['counters'][] = 0; // 新增計數器，預設值 0
    } elseif ($action === 'increase' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if (isset($_SESSION['counters'][$id])) {
            $_SESSION['counters'][$id]++;
        }
    } elseif ($action === 'decrease' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if (isset($_SESSION['counters'][$id])) {
            $_SESSION['counters'][$id]--;
        }
    } elseif ($action === 'reset') {
        $_SESSION['counters'] = [];
    }
    
    // 嚴禁 JS，利用 PHP 重新導向回自己 (避免 F5 重新發送 GET 但其實 GET 沒差)
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>C2 多重計數器</title>
</head>
<body>
    <h2>C2 多重計數器 (純 PHP / 無 JS)</h2>
    <a href="?action=add_counter"><button>新增計數器</button></a>
    <a href="?action=reset"><button>清空全部</button></a>
    <br><br>
    
    <div style="display:flex; flex-direction:column; gap:10px;">
    <?php foreach ($_SESSION['counters'] as $index => $value): ?>
        <div style="border:1px solid #ccc; padding:10px; width:200px; display:flex; justify-content:space-between; align-items:center;">
            <span>計數器 #<?= $index + 1 ?>: <strong><?= $value ?></strong></span>
            <div>
                <a href="?action=increase&id=<?= $index ?>"><button>+</button></a>
                <a href="?action=decrease&id=<?= $index ?>"><button>-</button></a>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if(empty($_SESSION['counters'])) echo "目前無計數器"; ?>
    </div>
    
    <br>
    <a href="../index.html">返回</a>
</body>
</html>
