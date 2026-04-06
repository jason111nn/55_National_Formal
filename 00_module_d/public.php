<?php
require_once 'db.php';

if ($route === 'verify') {
    $results = [];
    $allValid = true;
    $hasSubmit = false;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $hasSubmit = true;
        $isbns = explode("\n", $_POST['isbns'] ?? '');
        foreach($isbns as $rawLine) {
            $rawLine = trim($rawLine);
            if (empty($rawLine)) continue;
            
            $cleanIsbn = str_replace('-', '', $rawLine);
            $stmt = $pdo->prepare("SELECT b.id FROM books b JOIN publishers p ON b.publisher_id = p.id WHERE REPLACE(b.isbn, '-', '') = ? AND b.is_hidden = 0 AND p.status='active'");
            $stmt->execute([$cleanIsbn]);
            $valid = $stmt->fetch() ? true : false;
            
            if (!$valid) $allValid = false;
            $results[] = ['raw' => $rawLine, 'valid' => $valid];
        }
    }
    ?>
    <!DOCTYPE html><html><head><title>ISBN 批量驗證</title><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{font-family:sans-serif; padding:20px; max-width:600px; margin:0 auto;}</style></head>
    <body>
        <h2>公開 ISBN 批量驗證頁面</h2>
        <?php if($hasSubmit && count($results)>0 && $allValid): ?>
            <div style="background:#e8f5e9; color:#2e7d32; padding:15px; border-radius:5px; margin-bottom:15px; font-weight:bold;">
                ✅ All valid
            </div>
        <?php endif; ?>
        
        <?php if($hasSubmit): ?>
            <ul style="list-style:none; padding:0;">
                <?php foreach($results as $r): ?>
                    <li style="margin-bottom:10px; padding:10px; border:1px solid #ccc; <?= $r['valid'] ? 'border-left:5px solid green;' : 'border-left:5px solid red;' ?>">
                        <strong><?= htmlspecialchars($r['raw']) ?></strong> - <?= $r['valid'] ? '有效 (Valid)' : '無效 (Invalid)' ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <form method="post">
            <textarea name="isbns" rows="10" style="width:100%; font-family:monospace; padding:10px;" placeholder="每行輸入一個 ISBN 例: 978-986-181-728-6"></textarea><br><br>
            <button type="submit" style="padding:10px 20px;">驗證</button>
        </form>
    </body></html>
    <?php
    exit;

} elseif (preg_match('/^01\/(.+)$/', $route, $matches)) {
    $isbnReq = str_replace('-', '', $matches[1]);
    $stmt = $pdo->prepare("SELECT b.*, p.name as pname, p.id as pid FROM books b JOIN publishers p ON b.publisher_id = p.id WHERE REPLACE(b.isbn, '-', '') = ? AND b.is_hidden = 0 AND p.status='active'");
    $stmt->execute([$isbnReq]);
    $book = $stmt->fetch();
    
    if(!$book) {
        http_response_code(404);
        die("<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 書籍不存在或已下架</h1></body></html>");
    }
    
    $stmtI = $pdo->prepare("SELECT image_url FROM book_images WHERE book_id = ? ORDER BY is_cover DESC, id ASC");
    $stmtI->execute([$book['id']]);
    $images = $stmtI->fetchAll();
    ?>
    <!DOCTYPE html><html><head><title><?= htmlspecialchars($book['title']) ?></title><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{font-family:sans-serif; padding:15px; max-width:800px; margin:0 auto;} img{max-width:100%; height:auto; margin-bottom:10px;}</style></head>
    <body>
        <a href="/00_module_d/verify">返回驗證工具</a>
        <h1><?= htmlspecialchars($book['title']) ?></h1>
        <p><strong>作者：</strong> <?= htmlspecialchars($book['author']) ?></p>
        <p><strong>ISBN：</strong> <?= htmlspecialchars($book['isbn']) ?></p>
        <p><strong>出版社：</strong> <a href="/00_module_d/publisher/<?= $book['pid'] ?>"><?= htmlspecialchars($book['pname']) ?></a></p>
        <div style="background:#f9f9f9; padding:15px; border-radius:5px; margin:20px 0;">
            <p><?= nl2br(htmlspecialchars($book['description'])) ?></p>
        </div>
        <h3>書籍圖片</h3>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            <?php foreach($images as $img): ?>
                <div style="width:100%; max-width:300px;"><img src="/00_module_d/images/<?= $img['image_url'] ?>"></div>
            <?php endforeach; ?>
        </div>
    </body></html>
    <?php
    exit;

} elseif (preg_match('/^publisher\/(.+)$/', $route, $matches)) {
    $pid = (int)$matches[1];
    $stmt = $pdo->prepare("SELECT * FROM publishers WHERE id = ? AND status='active'");
    $stmt->execute([$pid]);
    $pub = $stmt->fetch();
    
    if(!$pub) {
        http_response_code(404);
        die("<!DOCTYPE html><html><head><title>404</title></head><body><h1>404 出版社不存在或已下架</h1></body></html>");
    }
    
    $stmtB = $pdo->prepare("SELECT b.*, (SELECT image_url FROM book_images WHERE book_id=b.id ORDER BY is_cover DESC LIMIT 1) as cover FROM books b WHERE publisher_id = ? AND is_hidden = 0");
    $stmtB->execute([$pid]);
    $books = $stmtB->fetchAll();
    ?>
    <!DOCTYPE html><html><head><title><?= htmlspecialchars($pub['name']) ?></title><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{font-family:sans-serif; padding:15px; max-width:800px; margin:0 auto;} .card{border:1px solid #ddd; padding:10px; margin-bottom:15px; display:flex; gap:15px; flex-direction:column;} @media(min-width:600px){.card{flex-direction:row;}} .c-img{width:100%; max-width:200px;}</style></head>
    <body>
        <h1>出版社：<?= htmlspecialchars($pub['name']) ?></h1>
        <p><strong>地址：</strong> <?= htmlspecialchars($pub['address']) ?></p>
        <p><strong>電話：</strong> <?= htmlspecialchars($pub['phone']) ?></p>
        <p><strong>出版社 ISBN 碼：</strong> <?= htmlspecialchars($pub['isbn_code']) ?></p>
        
        <hr>
        <h2>出版書籍清單</h2>
        <?php foreach($books as $b): ?>
            <div class="card">
                <?php if($b['cover']): ?>
                    <img class="c-img" src="/00_module_d/images/<?= $b['cover'] ?>">
                <?php else: ?>
                    <div class="c-img" style="background:#eee; display:flex; align-items:center; justify-content:center; height:200px;">無圖片</div>
                <?php endif; ?>
                <div>
                    <h3><a href="/00_module_d/01/<?= $b['isbn'] ?>"><?= htmlspecialchars($b['title']) ?></a></h3>
                    <p><strong>作者：</strong><?= htmlspecialchars($b['author']) ?> | <strong>ISBN：</strong><?= htmlspecialchars($b['isbn']) ?></p>
                    <p><?= nl2br(htmlspecialchars(substr($b['description'], 0, 150))) ?>...</p>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(count($books) === 0) echo "<p>目前無公開書籍。</p>"; ?>
    </body></html>
    <?php
    exit;
} else {
    http_response_code(404);
    echo "Page not found in Public scope.";
}
