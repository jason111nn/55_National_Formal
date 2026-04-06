<?php
// p07.php: Number of Islands
// Input: 
// 第一行: R C (列 欄)
// 接續 R 行: 0 或 1 (如 11000)

$handle = fopen("php://stdin", "r");
$lines = [];
while (($line = fgets($handle)) !== false) {
    if(trim($line) !== '') $lines[] = trim($line);
}
fclose($handle);

$idx = 0;
while ($idx < count($lines)) {
    $p = explode(' ', $lines[$idx++]);
    if(count($p) < 2) continue;
    $r = (int)$p[0];
    $c = (int)$p[1];
    
    $grid = [];
    for($i=0; $i<$r; $i++) {
        if($idx < count($lines)) {
            $grid[] = str_split(str_replace(' ', '', $lines[$idx++]));
        }
    }
    
    $islands = 0;
    for($i=0; $i<$r; $i++) {
        for($j=0; $j<$c; $j++) {
            if(isset($grid[$i][$j]) && $grid[$i][$j] === '1') {
                $islands++;
                dfs($grid, $i, $j, $r, $c);
            }
        }
    }
    echo "Islands: $islands\n";
}

function dfs(&$grid, $x, $y, $r, $c) {
    if($x<0 || $x>=$r || $y<0 || $y>=$c || $grid[$x][$y] === '0') return;
    $grid[$x][$y] = '0';
    dfs($grid, $x+1, $y, $r, $c);
    dfs($grid, $x-1, $y, $r, $c);
    dfs($grid, $x, $y+1, $r, $c);
    dfs($grid, $x, $y-1, $r, $c);
}
