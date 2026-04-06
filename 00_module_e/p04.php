<?php
// p04.php: Min Enclosing Circle
$handle = fopen("php://stdin", "r");
$lines = [];
while (($line = fgets($handle)) !== false) {
    if(trim($line) !== '') $lines[] = trim($line);
}
fclose($handle);

$idx = 0;
while($idx < count($lines)) {
    $n = (int)$lines[$idx++];
    $pts = [];
    for($i=0; $i<$n; $i++) {
        if($idx < count($lines)) {
            list($x, $y) = explode(' ', $lines[$idx++]);
            $pts[] = [(float)$x, (float)$y];
        }
    }
    shuffle($pts); // randomized for welzl
    $c = welzl($pts, [], count($pts));
    echo "Center: (" . round($c[0],4) . ", " . round($c[1],4) . "), Radius: " . round($c[2],4) . "\n";
}

function dist($p1, $p2) { return sqrt(pow($p1[0]-$p2[0], 2) + pow($p1[1]-$p2[1], 2)); }
function circle2($p1, $p2) { return [($p1[0]+$p2[0])/2, ($p1[1]+$p2[1])/2, dist($p1, $p2)/2]; }
function circle3($p1, $p2, $p3) {
    $bx=$p2[0]-$p1[0]; $by=$p2[1]-$p1[1];
    $cx=$p3[0]-$p1[0]; $cy=$p3[1]-$p1[1];
    $B = $bx*$bx + $by*$by; $C = $cx*$cx + $cy*$cy; $D = $bx*$cy - $by*$cx;
    if(abs($D) < 1e-9) { // collinear
        $d12 = dist($p1,$p2); $d13 = dist($p1,$p3); $d23 = dist($p2,$p3);
        if($d12 >= $d13 && $d12 >= $d23) return circle2($p1, $p2);
        if($d13 >= $d12 && $d13 >= $d23) return circle2($p1, $p3);
        return circle2($p2, $p3);
    }
    $cx_res = ($cy*$B - $by*$C) / (2*$D);
    $cy_res = ($bx*$C - $cx*$B) / (2*$D);
    $cent = [$p1[0]+$cx_res, $p1[1]+$cy_res];
    return [$cent[0], $cent[1], dist($cent, $p1)];
}
function welzl(&$P, $R, $n) {
    if ($n == 0 || count($R) == 3) {
        if(count($R)==0) return [0,0,0];
        if(count($R)==1) return [$R[0][0], $R[0][1], 0];
        if(count($R)==2) return circle2($R[0], $R[1]);
        return circle3($R[0], $R[1], $R[2]);
    }
    $p = $P[$n-1];
    $c = welzl($P, $R, $n-1);
    if(dist([$c[0], $c[1]], $p) <= $c[2] + 1e-9) return $c;
    $R[] = $p;
    return welzl($P, $R, $n-1);
}
