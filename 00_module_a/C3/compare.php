<?php
// 解析參數 (同時支援 GET 或 POST)
$before = $_REQUEST['before'] ?? '';
$after = $_REQUEST['after'] ?? '';
$position = isset($_REQUEST['position']) ? $_REQUEST['position'] : null;

// 當 position 超過圖片寬度、小於0、缺少、不是一個數字時，回傳 400 錯誤與 "invalid position"
if ($position === null || !is_numeric($position)) {
    http_response_code(400);
    die("invalid position");
}
$position = (int)$position;
if ($position < 0) {
    http_response_code(400);
    die("invalid position");
}

// 組合實際本機相對路徑 (放在 img/ 目錄下)
$beforePath = __DIR__ . '/img/' . basename($before);
$afterPath = __DIR__ . '/img/' . basename($after);

// 當 before 或 after 提供的檔案不存在，回傳 404
if (empty($before) || empty($after) || !file_exists($beforePath) || !file_exists($afterPath)) {
    http_response_code(404);
    die("404 Not Found");
}

$beforeInfo = getimagesize($beforePath);
$afterInfo = getimagesize($afterPath);

if (!$beforeInfo || !$afterInfo) {
    http_response_code(400);
    die("invalid image type");
}

// 當 before 和 after 大小不一致，回傳 400 與 size mismatch
if ($beforeInfo[0] !== $afterInfo[0] || $beforeInfo[1] !== $afterInfo[1]) {
    http_response_code(400);
    die("size mismatch");
}

$width = $beforeInfo[0];
$height = $beforeInfo[1];

if ($position > $width) {
    http_response_code(400);
    die("invalid position");
}

// 開始進行繪圖
$imgBefore = ($beforeInfo[2] == IMAGETYPE_PNG) ? imagecreatefrompng($beforePath) : imagecreatefromjpeg($beforePath);
$imgAfter = ($afterInfo[2] == IMAGETYPE_PNG) ? imagecreatefrompng($afterPath) : imagecreatefromjpeg($afterPath);

$output = imagecreatetruecolor($width, $height);

// 0 ~ position-1 畫 after 圖片
if ($position > 0) {
    imagecopy($output, $imgAfter, 0, 0, 0, 0, $position, $height);
}

// position+1 到圖片寬度 畫 before 圖片 (交界處將補上黃線所以是 $width - $position)
if ($position < $width) {
    imagecopy($output, $imgBefore, $position, 0, $position, 0, $width - $position, $height);
}

// position 畫一條黃色垂直線
$yellow = imagecolorallocate($output, 255, 255, 0);
imageline($output, $position, 0, $position, $height, $yellow);

header('Content-Type: image/jpeg');
imagejpeg($output);

imagedestroy($imgBefore);
imagedestroy($imgAfter);
imagedestroy($output);
?>
