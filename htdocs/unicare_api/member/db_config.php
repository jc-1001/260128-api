<?php
$host = '127.0.0.1';
$db   = 'unicare_db';
$user = 'root';
$pass = 'root'; // MAMP 預設通常是 root
$port = 8889;   // MAMP 預設 MySQL Port

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 如果這行沒報錯，代表連線成功
} catch (PDOException $e) {
    // 輸出 JSON 格式的錯誤訊息，方便 Vue 接收
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "資料庫連線失敗: " . $e->getMessage()]);
    exit;
}
?>