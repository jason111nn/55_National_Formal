<?php
// 基本設定，禁止暴露錯誤路徑
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
$base_dir = __DIR__ . '/content-pages';

// 取得相對路由
$request_uri = $_SERVER['REQUEST_URI'];
$base_path_str = '00_module_c';
$pos = strpos($request_uri, $base_path_str);
if ($pos !== false) {
    $route = substr($request_uri, $pos + strlen($base_path_str));
} else {
    $route = '/';
}
$route = explode('?', $route)[0]; // 移除 Query string
$route = trim($route, '/');

// 幫助函式：解析 Front-matter 與文字內容
function parseContent($rawContent, $filename, $slug) {
    $meta = [
        'title' => '', 'tags' => [], 'cover' => '', 
        'summary' => '', 'draft' => false, 
        'author' => 'Anonymous author', 'featured' => false
    ];
    $body = $rawContent;
    
    if (strpos($rawContent, "---") === 0) {
        $parts = explode("---", $rawContent, 3);
        if (count($parts) >= 3) {
            $frontmatter = $parts[1];
            $body = $parts[2];
            
            $lines = explode("\n", $frontmatter);
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($k, $v) = explode(':', $line, 2);
                    $k = trim(strtolower($k));
                    $v = trim($v);
                    if ($k === 'tags') {
                        $meta['tags'] = array_filter(array_map('trim', explode(',', $v)));
                    } elseif ($k === 'draft' || $k === 'featured') {
                        $meta[$k] = strtolower($v) === 'true';
                    } else {
                        $meta[$k] = $v;
                    }
                }
            }
        }
    }
    
    // Fallback title
    if (empty($meta['title'])) {
        if (preg_match('/<h1>(.*?)<\/h1>/i', $body, $matches)) {
            $meta['title'] = strip_tags($matches[1]);
        } else {
            $meta['title'] = strtoupper(str_replace('-', ' ', $slug));
        }
    }
    
    if (empty($meta['cover'])) {
        $meta['cover'] = str_replace(['.txt', '.html'], '.jpeg', $filename);
    }
    
    $meta['body'] = trim($body);
    return $meta;
}

// 遞迴掃描所有允許的檔案，供搜尋與標籤使用
function scanAllFilesDeep($dir, $relPath = '') {
    $results = [];
    $today = date('Y-m-d');
    if (!is_dir($dir)) return $results;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'images') continue;
        $path = $dir . '/' . $item;
        $currentRel = $relPath ? $relPath . '/' . $item : $item;
        
        if (is_dir($path)) {
            $results = array_merge($results, scanAllFilesDeep($path, $currentRel));
        } else {
            if (!preg_match('/^(\d{4}-\d{2}-\d{2})-(.*)\.(html|txt)$/', $item, $matches)) continue;
            $date = $matches[1];
            if ($date > $today) continue;
            
            $content = file_get_contents($path);
            $parsed = parseContent($content, $item, $matches[2]);
            if ($parsed['draft'] === true) continue;
            
            $parsed['type'] = 'file';
            $parsed['path'] = $currentRel; 
            $parsed['date'] = $date;
            $parsed['ext'] = $matches[3];
            $parsed['filename'] = $item;
            $results[] = $parsed;
        }
    }
    return $results;
}

// 只掃描單一層級，分出目錄與檔案 (首頁列表用)
function scanLevel($dir, $relPath = '') {
    $results = [];
    $today = date('Y-m-d');
    if (!is_dir($dir)) return $results;
    $items = scandir($dir);
    
    $folders = [];
    $files = [];

    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === 'images') continue;
        $path = $dir . '/' . $item;
        $currentRel = $relPath ? $relPath . '/' . $item : $item;
        
        if (is_dir($path)) {
            $folders[] = [
                'type' => 'folder',
                'name' => $item,
                'path' => $currentRel
            ];
        } else {
            if (!preg_match('/^(\d{4}-\d{2}-\d{2})-(.*)\.(html|txt)$/', $item, $matches)) continue;
            $date = $matches[1];
            if ($date > $today) continue;
            $content = file_get_contents($path);
            $parsed = parseContent($content, $item, $matches[2]);
            if ($parsed['draft'] === true) continue;
            
            $parsed['type'] = 'file';
            $parsed['path'] = $currentRel;
            $parsed['date'] = $date;
            $parsed['ext'] = $matches[3];
            $files[] = $parsed;
        }
    }
    
    // 排序：目錄字母順序，檔案逆字母順序 (讓最新日期在前)
    usort($folders, function($a, $b) { return strcmp($a['name'], $b['name']); });
    usort($files, function($a, $b) { return strcmp($b['path'], $a['path']); });
    
    return array_merge($folders, $files);
}

// 渲染 .txt 成 HTML
function renderTxtBody($body) {
    // 預防 \r
    $lines = explode("\n", str_replace("\r", "", $body));
    $html = "";
    $inQuote = false;
    $quoteContent = "";
    
    foreach ($lines as $line) {
        $tLine = trim($line);
        if ($tLine === '[quote]') { $inQuote = true; continue; }
        if ($tLine === '[/quote]') { 
            $inQuote = false; 
            $html .= "<blockquote style='border-left: 4px solid #ccc; padding-left:10px; color:#555;'>$quoteContent</blockquote>"; 
            $quoteContent = ""; 
            continue; 
        }
        if ($inQuote) { $quoteContent .= $tLine . "<br>"; continue; }
        
        if (empty($tLine)) continue;
        
        if (preg_match('/^[^\s]+\.(jpg|jpeg|png|gif)$/i', $tLine)) {
            $html .= "<img src='content-pages/images/{$tLine}' class='zoomable-img' style='width:100%; cursor:zoom-in;' alt='Image'>";
        } else {
            $html .= "<p>" . htmlspecialchars($tLine) . "</p>";
        }
    }
    return $html;
}

// 開始進行路由分流
$HTML_OUTPUT = "";
$SITE_BASE = "/00_module_c/"; // 如果使用絕對路徑修正資源載入
$query = $_GET['query'] ?? '';

if (!empty($query)) {
    // 搜尋功能
    $all = scanAllFilesDeep($base_dir);
    $keywords = array_filter(explode('/', strtolower($query)));
    
    $HTML_OUTPUT .= "<h2>Search Results for: " . htmlspecialchars($query) . "</h2>";
    $found = 0;
    foreach ($all as $item) {
        $match = false;
        $titleL = strtolower($item['title']);
        $bodyL = strtolower($item['body']);
        foreach ($keywords as $kw) {
            if (strpos($titleL, $kw) !== false || strpos($bodyL, $kw) !== false) {
                $match = true; break;
            }
        }
        if ($match) {
            $HTML_OUTPUT .= "<div style='margin-bottom:20px; border-bottom:1px solid #eee;'>";
            $HTML_OUTPUT .= "<h3><a href='events/{$item['path']}'>{$item['title']}</a></h3>";
            $HTML_OUTPUT .= "<p>{$item['summary']}</p></div>";
            $found++;
        }
    }
    if ($found == 0) $HTML_OUTPUT .= "<p>沒有找到相關文章</p>";

} elseif (strpos($route, 'tags') === 0) {
    $tagRoute = explode('/', $route);
    $all = scanAllFilesDeep($base_dir);
    
    if (isset($tagRoute[1]) && !empty($tagRoute[1])) {
        // 單一標籤列表
        $targetTag = strtolower(urldecode($tagRoute[1]));
        $HTML_OUTPUT .= "<h2>Tag: " . htmlspecialchars($targetTag) . "</h2>";
        foreach ($all as $item) {
            if (in_array($targetTag, array_map('strtolower', $item['tags']))) {
                $HTML_OUTPUT .= "<div style='margin-bottom:20px; border-bottom:1px solid #eee;'>";
                $HTML_OUTPUT .= "<h3><a href='../events/{$item['path']}'>{$item['title']}</a></h3>";
                $HTML_OUTPUT .= "<p>{$item['summary']}</p></div>";
            }
        }
    } else {
        // 全標籤彙整
        $allTags = [];
        foreach ($all as $item) {
            foreach ($item['tags'] as $t) {
                $allTags[strtolower($t)] = $t;
            }
        }
        sort($allTags);
        $HTML_OUTPUT .= "<h2>All Tags</h2><ul>";
        foreach ($allTags as $t) {
            $HTML_OUTPUT .= "<li><a href='tags/" . urlencode($t) . "'>{$t}</a></li>";
        }
        $HTML_OUTPUT .= "</ul>";
    }

} elseif (strpos($route, 'events') === 0) {
    // 文章顯示或子目錄顯示
    $targetPath = substr($route, 7); // skip "events/"
    $fullPath = $base_dir . '/' . $targetPath;
    
    if (is_dir($fullPath)) {
        // 渲染子目錄列表
        $items = scanLevel($fullPath, $targetPath);
        $HTML_OUTPUT .= "<h2>Folder: /" . htmlspecialchars($targetPath) . "</h2><ul>";
        foreach ($items as $item) {
            if ($item['type'] === 'folder') {
                $HTML_OUTPUT .= "<li>📁 <a href='events/{$item['path']}'>{$item['name']}</a></li>";
            } else {
                $icon = $item['featured'] ? '⭐' : '📄';
                $HTML_OUTPUT .= "<li>{$icon} <a href='events/{$item['path']}'>{$item['title']}</a> - {$item['summary']}</li>";
            }
        }
        $HTML_OUTPUT .= "</ul>";
    } elseif (file_exists($fullPath)) {
        // 渲染單篇文章
        $filename = basename($fullPath);
        if (preg_match('/^(\d{4}-\d{2}-\d{2})-(.*)\.(html|txt)$/', $filename, $matches)) {
            $content = file_get_contents($fullPath);
            $meta = parseContent($content, $filename, $matches[2]);
            
            // 附註資訊 Sticky header
            $featStr = $meta['featured'] ? "<strong>[精選文章]</strong> " : "";
            $tagStr = implode(', ', array_map(function($t){ return "<a href='tags/".urlencode($t)."'>$t</a>"; }, $meta['tags']));
            $draftStr = $meta['draft'] ? "<span style='color:red;'>[草稿]</span>" : "";
            
            $HTML_OUTPUT .= "
            <div id='meta-header' style='position:sticky; top:0; background:rgba(255,255,255,0.9); padding:10px; border-bottom:2px solid #ccc; z-index:100;'>
                {$featStr} {$draftStr} 
                Date: {$matches[1]} | Author: {$meta['author']} | Tags: {$tagStr}
            </div>";
            
            // Cover Image
            $HTML_OUTPUT .= "<div><img src='content-pages/images/{$meta['cover']}' style='width:100%; max-height:400px; object-fit:cover;' alt='Cover'></div>";
            
            // Title
            $HTML_OUTPUT .= "<h1>{$meta['title']}</h1>";
            
            // Main Content (Drop Cap apply logic via CSS)
            $HTML_OUTPUT .= "<div class='article-body'>";
            if ($matches[3] === 'html') {
                // 替換圖片路徑
                $htmlBody = preg_replace('/src=["\'](?!http)([^"\']+\.(jpg|jpeg|png|gif))["\']/i', 'src="content-pages/images/$1"', $meta['body']);
                // 加上 zoomable
                $htmlBody = str_replace('<img ', '<img class="zoomable-img" style="width:100%; cursor:zoom-in;" ', $htmlBody);
                $HTML_OUTPUT .= $htmlBody;
            } else {
                $HTML_OUTPUT .= renderTxtBody($meta['body']);
            }
            $HTML_OUTPUT .= "</div>";
            
            // 相關文章區塊
            $all = scanAllFilesDeep($base_dir);
            $related = [];
            foreach ($all as $item) {
                if ($item['path'] === $targetPath) continue;
                if (count(array_intersect(array_map('strtolower',$item['tags']), array_map('strtolower',$meta['tags']))) > 0) {
                    $related[] = $item;
                }
            }
            shuffle($related);
            $related = array_slice($related, 0, 3);
            if (count($related) > 0) {
                $HTML_OUTPUT .= "<h3>相關文章</h3><ul>";
                foreach ($related as $r) {
                    // /events 被截斷時處理 baseURL，為了不報路徑錯誤全用絕對或根相對
                    // 但因為題目規範，最好指回絕對
                    $HTML_OUTPUT .= "<li><a href='{$SITE_BASE}events/{$r['path']}'>{$r['title']}</a> - {$r['summary']}</li>";
                }
                $HTML_OUTPUT .= "</ul>";
            }
        }
    }

} else {
    // 首頁
    $HTML_OUTPUT .= "<h2>最新發布</h2><ul>";
    $all = scanAllFilesDeep($base_dir);
    usort($all, function($a, $b) { return strcmp($b['date'], $a['date']); });
    $latests = array_slice($all, 0, 5);
    foreach ($latests as $l) {
        $HTML_OUTPUT .= "<li>{$l['date']} - <a href='events/{$l['path']}'>{$l['title']}</a> ({$l['summary']})</li>";
    }
    $HTML_OUTPUT .= "</ul>";

    $HTML_OUTPUT .= "<h2>內容總管</h2><ul>";
    $items = scanLevel($base_dir);
    foreach ($items as $item) {
        if ($item['type'] === 'folder') {
            $HTML_OUTPUT .= "<li>📁 <a href='events/{$item['path']}'>{$item['name']}</a></li>";
        } else {
            $icon = $item['featured'] ? '⭐' : '📄';
            $HTML_OUTPUT .= "<li>{$icon} <a href='events/{$item['path']}'>{$item['title']}</a> - {$item['summary']}</li>";
        }
    }
    $HTML_OUTPUT .= "</ul>";
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>台北南港展覽館 內容展示系統</title>
    <base href="<?= $SITE_BASE ?>">
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; line-height: 1.6; }
        nav { margin-bottom: 20px; padding: 10px; background: #eee; display: flex; gap: 15px; align-items:center; }
        
        /* 頁面載入轉圈動畫 */
        #loader {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.9); z-index: 9999;
            display: flex; justify-content: center; align-items: center;
        }
        .spinner {
            width: 50px; height: 50px; border: 5px solid #ccc;
            border-top-color: #333; border-radius: 50%;
            animation: spin 1s infinite linear;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }
        
        /* Drop cap - 首段首字下沉 (依評審標準修正為實體 3 行高) */
        .article-body > p:first-of-type::first-letter {
            -webkit-initial-letter: 3 3;
            initial-letter: 3 3;
            color: #d35400; 
            font-weight: bold; 
            margin-right: 8px;
        }

        /* 圖片放大燈箱 */
        #lightbox {
            display: none; position: fixed; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.8); z-index: 10000;
            justify-content: center; align-items: center;
        }
        #lightbox img { max-width: 90%; max-height: 90%; border: 5px solid #fff; }
    </style>
</head>
<body>
    <div id="loader"><div class="spinner"></div></div>

    <nav>
        <a href="">首頁</a>
        <a href="tags">標籤清單</a>
        <form action="" method="GET" style="display:flex; gap:5px;">
            <input type="text" name="query" placeholder="Search title/content... '/' for OR">
            <button type="submit">發送搜尋</button>
        </form>
    </nav>

    <main>
        <?= $HTML_OUTPUT ?>
    </main>

    <!-- 圖片放大元件 -->
    <div id="lightbox">
        <img src="" id="lightbox-img" alt="Zoom In">
    </div>

    <script>
        // 載入動畫退出
        window.addEventListener('load', () => {
            setTimeout(() => { document.getElementById('loader').style.display = 'none'; }, 500);
        });

        // 圖片放大功能
        const lightbox = document.getElementById('lightbox');
        const lbImg = document.getElementById('lightbox-img');
        
        document.querySelectorAll('.zoomable-img').forEach(img => {
            img.addEventListener('click', (e) => {
                lbImg.src = e.target.src;
                lightbox.style.display = 'flex';
            });
        });

        // 點擊或捲動關閉燈箱
        lightbox.addEventListener('click', () => lightbox.style.display = 'none');
        window.addEventListener('scroll', () => { 
            if(lightbox.style.display === 'flex') lightbox.style.display = 'none'; 
        });
    </script>
</body>
</html>
