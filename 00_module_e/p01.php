<?php
// p01.php: Embedding Compare
// Input format assumption: 
// 第一行兩向量 A, 第二行向量 B (以空白相隔)，或每行包含 2 組以分號相隔的向量。
// 這裡假設每一行為一組測試資料，以 '/' 或 ';' 分開兩個向量。
$handle = fopen("php://stdin", "r");
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if(empty($line)) continue;
    $parts = preg_split('/[;\/|]/', $line);
    if(count($parts) < 2) continue;
    
    $v1 = array_map('floatval', array_filter(explode(' ', trim($parts[0])), 'strlen'));
    $v2 = array_map('floatval', array_filter(explode(' ', trim($parts[1])), 'strlen'));
    
    // Make equal length
    $len = max(count($v1), count($v2));
    $v1 = array_pad($v1, $len, 0.0);
    $v2 = array_pad($v2, $len, 0.0);
    
    $dot = 0.0; $normA = 0.0; $normB = 0.0; $eucSq = 0.0;
    for ($i=0; $i<$len; $i++) {
        $dot += $v1[$i] * $v2[$i];
        $normA += $v1[$i] * $v1[$i];
        $normB += $v2[$i] * $v2[$i];
        $diff = $v1[$i] - $v2[$i];
        $eucSq += $diff * $diff;
    }
    
    $cosim = ($normA == 0 || $normB == 0) ? 0 : $dot / (sqrt($normA) * sqrt($normB));
    $euc = sqrt($eucSq);
    
    echo "Cosine: " . round($cosim, 4) . " Euclidean: " . round($euc, 4) . "\n";
}
fclose($handle);
