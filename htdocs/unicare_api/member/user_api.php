<?php
// 萬能跨域許可 (解決前後台不同 Port 問題)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'db_config.php';

try {
    // 撈取所有會員資料
    $stmt = $pdo->query("SELECT * FROM members ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 直接輸出 JSON
    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>