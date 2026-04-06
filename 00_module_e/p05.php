<?php
// p05.php: Custom Tag Expander
// Input Example: 
// [Templates]
// t1: <div>{content}</div>
// [Document]
// <custom id="t1">Hello</custom>
$handle = fopen("php://stdin", "r");
$mode = 0; $templates = []; $docs = [];
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if(empty($line)) continue;
    if($line === '[Templates]') { $mode = 1; continue; }
    if($line === '[Document]') { $mode = 2; continue; }
    
    if($mode == 1) {
        if(preg_match('/^([\w\-]+):\s*(.*)$/', $line, $m)) {
            $templates[$m[1]] = $m[2];
        }
    } elseif($mode == 2) {
        $docs[] = $line;
    }
}
fclose($handle);

$docStr = implode(' ', $docs);
echo expandTags($docStr, $templates, 0) . "\n";

function expandTags($str, $templates, $depth) {
    if ($depth > 100) return $str; // 簡單防遞迴爆炸
    return preg_replace_callback('/<custom\s+id=["\']([\w\-]+)["\']>(.*?)<\/custom>/s', function($m) use ($templates, $depth) {
        $tid = $m[1];
        $inner = expandTags($m[2], $templates, $depth + 1); // 處理巢狀
        
        if (isset($templates[$tid])) {
            $tpl = $templates[$tid];
            return expandTags(str_replace('{content}', $inner, $tpl), $templates, $depth + 1);
        } else {
            return $inner; // Fallback
        }
    }, $str);
}
