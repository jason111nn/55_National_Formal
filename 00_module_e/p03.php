<?php
// p03.php: JSON VDOM Diff
// Input two lines per case: Old JSON, New JSON
$handle = fopen("php://stdin", "r");
$oldStr = "";
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if(empty($line)) continue;
    if (!$oldStr) {
        $oldStr = $line;
    } else {
        $old = json_decode($oldStr, true);
        $new = json_decode($line, true);
        $diff = getDiff($old, $new);
        echo json_encode($diff, JSON_UNESCAPED_UNICODE) . "\n";
        $oldStr = "";
    }
}

function getDiff($old, $new, $path = "") {
    $diffs = [];
    if (!is_array($old)) $old = [];
    if (!is_array($new)) $new = [];
    
    foreach ($new as $k => $v) {
        $curPath = $path ? "$path.$k" : $k;
        if (!array_key_exists($k, $old)) {
            $diffs[] = ['type' => 'ADD', 'path' => $curPath, 'value' => $v];
        } elseif (is_array($v) && is_array($old[$k])) {
            $diffs = array_merge($diffs, getDiff($old[$k], $v, $curPath));
        } elseif ($v !== $old[$k]) {
            $diffs[] = ['type' => 'UPDATE', 'path' => $curPath, 'old' => $old[$k], 'new' => $v];
        }
    }
    foreach ($old as $k => $v) {
        $curPath = $path ? "$path.$k" : $k;
        if (!array_key_exists($k, $new)) {
            $diffs[] = ['type' => 'REMOVE', 'path' => $curPath];
        }
    }
    return $diffs;
}
