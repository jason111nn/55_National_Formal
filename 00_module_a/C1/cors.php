<?php
// C1 API CORS: 允許跨域請求 (GET, POST, PUT, DELETE)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 提供簡單的 JSON 回應以供測試
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'msg' => 'CORS Headers are correctly set for GET, POST, PUT, DELETE.',
    'method' => $_SERVER['REQUEST_METHOD']
]);
?>
