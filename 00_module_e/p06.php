<?php
// p06.php: Coin Change (Greedy 50, 10, 5, 1)
// Input: Amount
$handle = fopen("php://stdin", "r");
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if(empty($line)) continue;
    $v = intval($line);
    if ($v < 0) continue;
    
    $c50 = floor($v / 50); $v %= 50;
    $c10 = floor($v / 10); $v %= 10;
    $c5 = floor($v / 5); $v %= 5;
    $c1 = $v;
    $tot = $c50 + $c10 + $c5 + $c1;
    
    echo "50: $c50, 10: $c10, 5: $c5, 1: $c1, Total count: $tot\n";
}
fclose($handle);
