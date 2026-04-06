<?php
// p02.php: Emmet Expander
// Input: div#main.box>span*3
// Output: <div id="main" class="box"><span></span><span></span><span></span></div>
$handle = fopen("php://stdin", "r");
while (($line = fgets($handle)) !== false) {
    $line = trim($line);
    if(empty($line)) continue;
    
    echo expandEmmet($line) . "\n";
}

function expandEmmet($str) {
    // 粗略拆分 >
    $parts = explode('>', $str);
    $html = "";
    $stack = [];
    foreach ($parts as $p) {
        $multiplier = 1;
        if (strpos($p, '*') !== false) {
            list($p, $m) = explode('*', $p);
            $multiplier = (int)$m;
        }
        
        $tag = 'div'; $id = ''; $classes = [];
        preg_match_all('/([a-zA-Z0-9]+)|(#[\w\-]+)|(\.[\w\-]+)/', $p, $matches);
        
        foreach ($matches[0] as $m) {
            if ($m[0] === '#') $id = substr($m, 1);
            elseif ($m[0] === '.') $classes[] = substr($m, 1);
            else $tag = $m;
        }
        
        $attrStr = ($id ? " id=\"$id\"" : "") . (!empty($classes) ? " class=\"" . implode(' ', $classes) . "\"" : "");
        $open = "<{$tag}{$attrStr}>";
        $close = "</{$tag}>";
        
        $innerStr = "";
        for($i=0; $i<$multiplier; $i++) {
            $innerStr .= $open . "{INNER}" . $close;
        }
        $stack[] = $innerStr;
    }
    
    $res = array_pop($stack);
    $res = str_replace("{INNER}", "", $res);
    while (count($stack) > 0) {
        $parent = array_pop($stack);
        $res = str_replace("{INNER}", $res, $parent);
        $res = str_replace("{INNER}", "", $res); 
    }
    
    return $res;
}
