<?php
$user = requireAuth(['super']); // 只有 superadmin 能訪問
$pRoute = substr($route, strlen('publishers'));
$pRoute = trim($pRoute, '/');

// toggle status
if (isset($_GET['action']) && isset($_GET['id']) && in_array($_GET['action'], ['active', 'inactive'])) {
    $aid = (int)$_GET['id'];
    $st = $_GET['action'];
    $pdo->prepare("UPDATE publishers SET status = ? WHERE id = ?")->execute([$st, $aid]);
    header("Location: /00_module_d/publishers");
    exit;
}

// 處理新增或修改 Publisher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_publisher'])) {
    $pid = $_POST['publisher_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $isbn_code = trim($_POST['isbn_code'] ?? '');
    
    // Contacts: arrays
    $c_names = $_POST['c_name'] ?? [];
    $c_phones = $_POST['c_phone'] ?? [];
    $c_emails = $_POST['c_email'] ?? [];
    
    $err = "";
    // ISBN 碼防重複
    $ck = $pdo->prepare("SELECT id FROM publishers WHERE isbn_code = ? AND id != ?");
    $ck->execute([$isbn_code, (int)$pid]);
    if ($ck->fetch()) $err = "出版社 ISBN 代碼與其他出版社重複！";
    
    // Check contact count
    $validContacts = 0;
    for ($i=0; $i<count($c_names); $i++) {
        if(trim($c_names[$i]) && trim($c_phones[$i]) && trim($c_emails[$i])) $validContacts++;
    }
    if ($validContacts < 1) $err = "至少需要設定一位聯絡人資訊！";
    
    if (!$err) {
        $pdo->beginTransaction();
        try {
            if ($pid) {
                $pdo->prepare("UPDATE publishers SET name=?, address=?, phone=?, isbn_code=? WHERE id=?")
                    ->execute([$name, $address, $phone, $isbn_code, $pid]);
                // 重建 contacts (簡化作法：全刪除再重建)
                $pdo->prepare("DELETE FROM contacts WHERE publisher_id=?")->execute([$pid]);
            } else {
                $pdo->prepare("INSERT INTO publishers (name, address, phone, isbn_code) VALUES (?,?,?,?)")
                    ->execute([$name, $address, $phone, $isbn_code]);
                $pid = $pdo->lastInsertId();
            }
            
            // Insert contacts
            $stmtC = $pdo->prepare("INSERT INTO contacts (publisher_id, name, phone, email) VALUES (?,?,?,?)");
            for ($i=0; $i<count($c_names); $i++) {
                if(trim($c_names[$i]) && trim($c_phones[$i]) && trim($c_emails[$i])) {
                    $stmtC->execute([$pid, trim($c_names[$i]), trim($c_phones[$i]), trim($c_emails[$i])]);
                }
            }
            $pdo->commit();
            header("Location: /00_module_d/publishers");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $err = "發生錯誤：" . $e->getMessage();
        }
    }
}

// 處理新增/編輯 Administrator
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin'])) {
    $uid = $_POST['admin_id'] ?? null;
    $pubId = $_POST['publisher_id'];
    $uname = trim($_POST['username'] ?? '');
    $upass = $_POST['password'] ?? '';
    $rname = trim($_POST['realname'] ?? '');
    
    if ($uid) {
        if ($upass) {
            $pdo->prepare("UPDATE admins SET username=?, password_hash=?, name=? WHERE id=?")
                ->execute([$uname, hash('sha256', $upass), $rname, $uid]);
        } else {
            $pdo->prepare("UPDATE admins SET username=?, name=? WHERE id=?")
                ->execute([$uname, $rname, $uid]);
        }
    } else {
        $pdo->prepare("INSERT INTO admins (username, password_hash, role, publisher_id, name) VALUES (?,?,?,?,?)")
            ->execute([$uname, hash('sha256', $upass), 'publisher', $pubId, $rname]);
    }
    header("Location: /00_module_d/publishers/" . $pubId);
    exit;
}
if (isset($_GET['del_admin'])) {
    $del = (int)$_GET['del_admin'];
    $back = (int)$_GET['back'];
    $pdo->prepare("DELETE FROM admins WHERE id=?")->execute([$del]);
    header("Location: /00_module_d/publishers/" . $back);
    exit;
}

if ($pRoute === 'new' || ($pRoute !== '' && $pRoute !== 'new')) {
    $pub = null;
    $contacts = [];
    $admins = [];
    if ($pRoute !== 'new') {
        $stmtP = $pdo->prepare("SELECT * FROM publishers WHERE id = ?");
        $stmtP->execute([(int)$pRoute]);
        $pub = $stmtP->fetch();
        if(!$pub) die("404");
        
        $contacts = $pdo->query("SELECT * FROM contacts WHERE publisher_id = $pub[id]")->fetchAll();
        $admins = $pdo->query("SELECT * FROM admins WHERE publisher_id = $pub[id] AND role='publisher'")->fetchAll();
    }
    ?>
    <!DOCTYPE html><html><head><title><?= $pub?'編輯出版社':'新增出版社' ?></title><style>body{font-family:sans-serif; margin:20px;} .c-row{display:flex; gap:10px; margin-bottom:5px;}</style></head>
    <body>
        <h2><?= $pub? '編輯出版社' : '新增出版社' ?></h2>
        <?php if(isset($err) && $err) echo "<p style='color:red'>$err</p>"; ?>
        <form method="post">
            <?php if($pub): ?> <input type="hidden" name="publisher_id" value="<?= $pub['id'] ?>"> <?php endif; ?>
            <div>名稱: <input type="text" name="name" value="<?= htmlspecialchars($pub['name']??'') ?>" required></div><br>
            <div>地址: <input type="text" name="address" value="<?= htmlspecialchars($pub['address']??'') ?>" required></div><br>
            <div>電話: <input type="text" name="phone" value="<?= htmlspecialchars($pub['phone']??'') ?>" required></div><br>
            <div>ISBN 代碼: <input type="text" name="isbn_code" value="<?= htmlspecialchars($pub['isbn_code']??'') ?>" required></div><br>
            
            <h3>聯絡人資訊 (至少一筆)</h3>
            <div id="contact-wrapper">
                <?php if($pub): foreach($contacts as $c): ?>
                    <div class="c-row">
                        <input type="text" name="c_name[]" value="<?= htmlspecialchars($c['name']) ?>" placeholder="姓名" required>
                        <input type="text" name="c_phone[]" value="<?= htmlspecialchars($c['phone']) ?>" placeholder="電話" required>
                        <input type="email" name="c_email[]" value="<?= htmlspecialchars($c['email']) ?>" placeholder="Email" required>
                    </div>
                <?php endforeach; else: ?>
                    <div class="c-row">
                        <input type="text" name="c_name[]" placeholder="姓名" required>
                        <input type="text" name="c_phone[]" placeholder="電話" required>
                        <input type="email" name="c_email[]" placeholder="Email" required>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" onclick="addContact()">+ 新增聯絡人</button><br><br>
            
            <button type="submit" name="save_publisher" style="background:#2e7d32;color:#fff;padding:10px;">儲存出版社資訊</button>
            <a href="/00_module_d/publishers">返回列表</a>
        </form>

        <script>
            function addContact() {
                const wrap = document.getElementById('contact-wrapper');
                const row = document.createElement('div');
                row.className = 'c-row';
                row.innerHTML = '<input type="text" name="c_name[]" placeholder="姓名"><input type="text" name="c_phone[]" placeholder="電話"><input type="email" name="c_email[]" placeholder="Email"><button type="button" onclick="this.parentElement.remove()">移除</button>';
                wrap.appendChild(row);
            }
        </script>
        
        <?php if($pub): ?>
        <hr>
        <h2>所屬出版社管理員 (Admins)</h2>
        <table border="1" cellpadding="5" cellspacing="0" style="margin-bottom:15px; width:100%;">
            <tr><th>帳號 (Username)</th><th>真實姓名 (Name)</th><th>操作</th></tr>
            <?php foreach($admins as $a): ?>
            <tr>
                <td><?= htmlspecialchars($a['username']) ?></td>
                <td><?= htmlspecialchars($a['name']) ?></td>
                <td>
                    <button onclick="document.getElementById('a_id').value='<?= $a['id'] ?>'; document.getElementById('a_un').value='<?= htmlspecialchars($a['username']) ?>'; document.getElementById('a_rn').value='<?= htmlspecialchars($a['name']) ?>';">編輯</button> | 
                    <a href="?del_admin=<?= $a['id'] ?>&back=<?= $pub['id'] ?>" onclick="return confirm('刪除此管理員?')" style="color:red;">刪除</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <form method="post" style="border:1px solid #ccc; padding:15px;">
            <h3>新增/編輯 管理員</h3>
            <input type="hidden" name="publisher_id" value="<?= $pub['id'] ?>">
            <input type="hidden" name="admin_id" id="a_id" value="">
            帳號: <input type="text" name="username" id="a_un" required>
            名稱: <input type="text" name="realname" id="a_rn" required>
            密碼: <input type="password" name="password" id="a_pw" placeholder="(編輯時空白表示不改)">
            <button type="submit" name="save_admin">儲存管理員</button>
            <button type="button" onclick="document.getElementById('a_id').value=''; document.getElementById('a_un').value=''; document.getElementById('a_rn').value=''; document.getElementById('a_pw').value='';">清空 (改為新增)</button>
        </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
} else {
    // List publishers
    $pubs = $pdo->query("SELECT * FROM publishers ORDER BY status ASC, id DESC")->fetchAll();
    ?>
    <!DOCTYPE html><html><head><title>出版社管理</title><style>body{font-family:sans-serif; margin:20px;} table{border-collapse:collapse; width:100%;} th,td{border:1px solid #ddd; padding:8px;} .inactive{background:#fce4e4; color:#888;}</style></head>
    <body>
        <h2>出版社管理清單</h2>
        <a href="/00_module_d/publishers/new" style="display:inline-block; margin-bottom:10px; background:#2e7d32; color:#fff; padding:5px 10px; text-decoration:none;">+ 新增出版社</a>
        <a href="/00_module_d/books" style="margin-left:15px;">切換至書籍管理</a>
        <a href="/00_module_d/logout" style="float:right;">登出</a>
        
        <h3>啟用的出版社</h3>
        <table>
            <tr><th>名稱</th><th>地址</th><th>電話</th><th>ISBN代碼</th><th>操作</th></tr>
            <?php foreach($pubs as $p): if($p['status']==='active'): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['address']) ?></td>
                <td><?= htmlspecialchars($p['phone']) ?></td>
                <td><?= htmlspecialchars($p['isbn_code']) ?></td>
                <td>
                    <a href="/00_module_d/publishers/<?= $p['id'] ?>">檢視/編輯</a> | 
                    <a href="?action=inactive&id=<?= $p['id'] ?>" onclick="return confirm('確實停用?');" style="color:orange;">停用</a>
                </td>
            </tr>
            <?php endif; endforeach; ?>
        </table>
        
        <h3 style="margin-top:40px;">已停用的出版社</h3>
        <table>
            <tr><th>名稱</th><th>地址</th><th>電話</th><th>ISBN代碼</th><th>操作</th></tr>
            <?php foreach($pubs as $p): if($p['status']==='inactive'): ?>
            <tr class="inactive">
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['address']) ?></td>
                <td><?= htmlspecialchars($p['phone']) ?></td>
                <td><?= htmlspecialchars($p['isbn_code']) ?></td>
                <td>
                    <a href="/00_module_d/publishers/<?= $p['id'] ?>">檢視/編輯</a> | 
                    <a href="?action=active&id=<?= $p['id'] ?>" style="color:green;">啟用</a>
                </td>
            </tr>
            <?php endif; endforeach; ?>
        </table>
    </body></html>
    <?php
}
