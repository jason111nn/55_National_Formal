<?php
// p08.php: Longest Valid Parentheses
// Input: sequence of (), e.g. (()()
$handle = fopen("php://stdin", "r");
while (($line = fgets($handle)) !== false) {
    $s = trim($line);
    if($s === '') continue;
    
    $left = 0; $right = 0; $max = 0;
    for ($i=0; $i<strlen($s); $i++) {
        if ($s[$i] == '(') $left++; else $right++;
        if ($left == $right) $max = max($max, 2 * $right);
        else if ($right > $left) { $left = 0; $right = 0; }
    }
    
    $left = 0; $right = 0;
    for ($i=strlen($s)-1; $i>=0; $i--) {
        if ($s[$i] == '(') $left++; else $right++;
        if ($left == $right) $max = max($max, 2 * $left);
        else if ($left > $right) { $left = 0; $right = 0; }
    }
    
    echo "Longest valid length: " . $max . "\n";
}
fclose($handle);
