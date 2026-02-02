<?php
// 放在最頂端，確保老師來的時候如果有錯會直接顯示在螢幕上
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';
$db   = 'unicare_db'; // 請確認資料庫名稱是否正確
$user = 'root';
$pass = 'root'; 
$port = 8889;   

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass);
    // 設定錯誤模式為 Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // 如果連線失敗，直接印出錯誤訊息，這對 Debug 很有幫助
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(["success" => false, "message" => "資料庫連線失敗: " . $e->getMessage()]);
    exit;
}
?>