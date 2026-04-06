<?php
$user = requireAuth(['super', 'publisher']);
$bRoute = substr($route, strlen('books'));
$bRoute = trim($bRoute, '/');

$isSuper = $user['role'] === 'super';
$myPubId = $user['publisher_id'];

// Handle quick actions: hide, show, delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $aid = (int)$_GET['id'];
    // 檢查權限
    $stmtC = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmtC->execute([$aid]);
    $tb = $stmtC->fetch();
    
    if ($tb && ($isSuper || $tb['publisher_id'] == $myPubId)) {
        if ($_GET['action'] === 'hide') {
            $pdo->prepare("UPDATE books SET is_hidden = 1 WHERE id = ?")->execute([$aid]);
        } elseif ($_GET['action'] === 'show') {
            $pdo->prepare("UPDATE books SET is_hidden = 0 WHERE id = ?")->execute([$aid]);
        } elseif ($_GET['action'] === 'delete' && $tb['is_hidden'] == 1) {
            $pdo->prepare("DELETE FROM books WHERE id = ?")->execute([$aid]);
        }
    }
    header("Location: /00_module_d/books");
    exit;
}

// 處理新增或編輯的 POST (這邊預留給分離的檢視區處理，或共用)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_book'])) {
    $bid = $_POST['book_id'] ?? null;
    $isbn12 = $_POST['isbn12'] ?? ''; // 前12碼
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $pubId = $_POST['publisher_id'] ?? $myPubId;
    
    if (!$isSuper) $pubId = $myPubId;
    
    $err = "";
    // ISBN 運算 (只有新增或更改 ISBN12 時檢驗)
    $clean12 = str_replace('-', '', $isbn12);
    if(strlen($clean12) !== 12) {
        $err = "ISBN前12碼格式錯誤";
    }
    
    $checkDigit = calculateIsbnCheckDigit($clean12);
    $fullIsbn = $isbn12 . '-' . $checkDigit;
    
    // 取出 Publisher ISBN Code 檢查
    $stmtP = $pdo->prepare("SELECT isbn_code FROM publishers WHERE id = ?");
    $stmtP->execute([$pubId]);
    $pubCode = $stmtP->fetchColumn();
    
    if (strpos($clean12, str_replace('-', '', $pubCode)) === false) {
        // "ISBN 中的出版社代碼需要與選擇的出版社相同" => 採用寬鬆或嚴格包含比對
        $err = "ISBN 中不包含或不符預期的出版社代碼 ($pubCode)";
    }
    
    if(!$err) {
        if ($bid) {
            // Edit
            $pdo->prepare("UPDATE books SET isbn=?, title=?, description=?, author=?, publisher_id=? WHERE id=?")
                ->execute([$fullIsbn, $title, $desc, $author, $pubId, $bid]);
        } else {
            // Check unique
            $ck = $pdo->prepare("SELECT id FROM books WHERE REPLACE(isbn,'-','') = ?");
            $ck->execute([str_replace('-','',$fullIsbn)]);
            if($ck->fetch()) {
                $err = "該 ISBN 已存在!";
            } else {
                $pdo->prepare("INSERT INTO books (isbn, title, description, author, publisher_id) VALUES (?,?,?,?,?)")
                    ->execute([$fullIsbn, $title, $desc, $author, $pubId]);
                $bid = $pdo->lastInsertId();
            }
        }
        
        // 處理圖片上傳
        if (!$err && !empty($_FILES['images']['name'][0])) {
            $hasCover = $pdo->query("SELECT id FROM book_images WHERE book_id=$bid AND is_cover=1")->fetch();
            $setCover = !$hasCover;
            foreach($_FILES['images']['tmp_name'] as $idx => $tmp) {
                if(is_uploaded_file($tmp)) {
                    $ext = pathinfo($_FILES['images']['name'][$idx], PATHINFO_EXTENSION);
                    $newname = uniqid("bk_").".".$ext;
                    move_uploaded_file($tmp, __DIR__ . '/images/' . $newname);
                    
                    $isC = $setCover ? 1 : 0;
                    $pdo->prepare("INSERT INTO book_images (book_id, image_url, is_cover) VALUES (?,?,?)")
                        ->execute([$bid, $newname, $isC]);
                    $setCover = false;
                }
            }
        }
        
        if(!$err) {
            header("Location: /00_module_d/books");
            exit;
        }
    }
}

// 圖片刪除/設為封面
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['img_action'])) {
    $imgId = (int)$_POST['img_id'];
    $bid = (int)$_POST['book_id'];
    // TODO: 驗證權限
    if ($_POST['img_action'] === 'delete') {
        $pdo->prepare("DELETE FROM book_images WHERE id=?")->execute([$imgId]);
    } elseif ($_POST['img_action'] === 'cover') {
        $pdo->prepare("UPDATE book_images SET is_cover=0 WHERE book_id=?")->execute([$bid]);
        $pdo->prepare("UPDATE book_images SET is_cover=1 WHERE id=?")->execute([$imgId]);
    }
    header("Location: /00_module_d/books/" . $_POST['isbn_back']);
    exit;
}

if ($bRoute === 'new' || ($bRoute !== '' && $bRoute !== 'new')) {
    // 編輯與新增模式
    $book = null;
    $images = [];
    if ($bRoute !== 'new') {
        $stmtB = $pdo->prepare("SELECT * FROM books WHERE REPLACE(isbn,'-','') = ?");
        $stmtB->execute([str_replace('-','', $bRoute)]);
        $book = $stmtB->fetch();
        if(!$book || (!$isSuper && $book['publisher_id'] != $myPubId)) die("403 or 404");
        
        $stmtI = $pdo->prepare("SELECT * FROM book_images WHERE book_id=? ORDER BY is_cover DESC");
        $stmtI->execute([$book['id']]);
        $images = $stmtI->fetchAll();
    }
    
    $pubs = [];
    if ($isSuper) {
        $pubs = $pdo->query("SELECT id, name FROM publishers")->fetchAll();
    } else {
        $pubs = $pdo->query("SELECT id, name FROM publishers WHERE id = $myPubId")->fetchAll();
    }
    
    ?>
    <!DOCTYPE html><html><head><title><?= $book ? '編輯書籍' : '新增書籍' ?></title><style>body{font-family:sans-serif; margin:20px;}</style></head>
    <body>
        <h2><?= $book ? '編輯書籍' : '新增書籍' ?></h2>
        <?php if(isset($err) && $err) echo "<p style='color:red'>$err</p>"; ?>
        <form method="post" enctype="multipart/form-data">
            <?php if($book): ?>
                <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
            <?php endif; ?>
            <div>書籍名稱: <input type="text" name="title" value="<?= $book['title'] ?? '' ?>" required></div><br>
            <div>作者: <input type="text" name="author" value="<?= $book['author'] ?? '' ?>" required></div><br>
            <div>ISBN (前12碼，系統自動算驗證碼): 
                <input type="text" name="isbn12" value="<?= $book ? substr($book['isbn'], 0, strrpos($book['isbn'],'-')?:12) : '' ?>" required>
                <?php if($book) echo "目前完整: {$book['isbn']}"; ?>
            </div><br>
            <div>出版社:
                <select name="publisher_id" required>
                    <?php foreach($pubs as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($book && $book['publisher_id']==$p['id'])?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div><br>
            <div>描述:<br><textarea name="description" rows="5" cols="50" required><?= htmlspecialchars($book['description'] ?? '') ?></textarea></div><br>
            <div>上傳圖片 (可多選): <input type="file" name="images[]" multiple></div><br>
            <button type="submit" name="save_book">儲存書籍</button>
            <a href="/00_module_d/books">取消返回</a>
        </form>
        
        <?php if($book && count($images)>0): ?>
            <hr>
            <h3>現有圖片管理</h3>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php foreach($images as $img): ?>
                <div style="border:1px solid #ccc; padding:10px; text-align:center;">
                    <img src="/00_module_d/images/<?= $img['image_url'] ?>" style="height:100px; display:block;"><br>
                    <?= $img['is_cover'] ? '<strong>[封面]</strong><br>' : '' ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="img_id" value="<?= $img['id'] ?>">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <input type="hidden" name="isbn_back" value="<?= $book['isbn'] ?>">
                        <button type="submit" name="img_action" value="cover">設為封面</button>
                        <button type="submit" name="img_action" value="delete" onclick="return confirm('刪除圖片?')">刪除</button>
                    </form>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </body></html>
    <?php
    exit;

} else {
    // List books
    $q = $_GET['q'] ?? '';
    
    $where = "1=1";
    $params = [];
    if (!$isSuper) {
        $where .= " AND b.publisher_id = ?";
        $params[] = $myPubId;
    }
    if ($q) {
        $where .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
        $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
    }
    
    $stmt = $pdo->prepare("SELECT b.*, p.name as pname, 
        (SELECT image_url FROM book_images WHERE book_id=b.id ORDER BY is_cover DESC LIMIT 1) as cover 
        FROM books b JOIN publishers p ON b.publisher_id=p.id 
        WHERE $where ORDER BY b.id DESC");
    $stmt->execute($params);
    $books = $stmt->fetchAll();
    ?>
    <!DOCTYPE html><html><head><title>書籍管理</title><style>body{font-family:sans-serif; margin:20px;} table{border-collapse:collapse; width:100%;} th,td{border:1px solid #ddd; padding:8px;} </style></head>
    <body>
        <h2>書籍管理清單 (帳號角色: <?= htmlspecialchars($user['role']) ?>)</h2>
        <a href="/00_module_d/books/new" style="display:inline-block; margin-bottom:10px; background:#2e7d32; color:#fff; padding:5px 10px; text-decoration:none;">+ 新增書籍</a>
        <a href="/00_module_d/logout" style="float:right;">登出</a>
        <?php if($isSuper): ?> <a href="/00_module_d/publishers" style="float:right; margin-right:20px;">出版社管理</a> <?php endif; ?>
        
        <form method="get" style="margin-bottom:20px;">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="搜尋書名/作者/ISBN...">
            <button type="submit">過濾</button>
        </form>
        
        <table>
            <tr><th>封面</th><th>書名</th><th>作者</th><th>ISBN</th><th>出版社</th><th>狀態</th><th>操作</th></tr>
            <?php foreach($books as $b): ?>
            <tr style="<?= $b['is_hidden'] ? 'background:#f9f9f9; color:#888;' : '' ?>">
                <td>
                    <?php if($b['cover']): ?> <img src="/00_module_d/images/<?= $b['cover'] ?>" width="50">
                    <?php else: ?> 無 <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($b['title']) ?></td>
                <td><?= htmlspecialchars($b['author']) ?></td>
                <td><?= htmlspecialchars($b['isbn']) ?></td>
                <td><?= htmlspecialchars($b['pname']) ?></td>
                <td><?= $b['is_hidden'] ? '<span style="color:red">已隱藏</span>' : '<span style="color:green">公開</span>' ?></td>
                <td>
                    <a href="/00_module_d/books/<?= $b['isbn'] ?>">編輯</a> | 
                    <?php if($b['is_hidden']): ?>
                        <a href="/00_module_d/books?action=show&id=<?= $b['id'] ?>">上架</a> | 
                        <a href="/00_module_d/books?action=delete&id=<?= $b['id'] ?>" onclick="return confirm('確定永久刪除?');" style="color:red;">永久刪除</a>
                    <?php else: ?>
                        <a href="/00_module_d/books?action=hide&id=<?= $b['id'] ?>">下架隱藏</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </body></html>
    <?php
}
