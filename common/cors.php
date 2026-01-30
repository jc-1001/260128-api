<?php
// 1. 如果之後要用 Session，這行必須在 header 送出前執行
// session_start(); 

// 2. 定義允許連線的白名單
$allowed_origins = [
    "http://localhost:5173",
    "http://localhost:5174",
    "http://localhost:8888",
    "https://tibamef2e.com",
    "https://fc28ef460f6f.ngrok-free.app", // 給 Ngrok 測試用
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? ''; 

// 3. 動態判定 Origin
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true");
}

// 4. 設定允許的方法與 Headers
header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 5. 處理 Preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204); 
    exit; 
}
?>