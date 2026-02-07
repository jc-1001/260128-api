<?php
// Error顯示於螢幕
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';
$db   = 'unicare_db'; // 資料庫名
$user = 'root';
$pass = 'root'; 
$port = 8889;   

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass);
    // 設定Error 為 Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // 連線失敗原因
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(["success" => false, "message" => "資料庫連線失敗: " . $e->getMessage()]);
    exit;
}
?>