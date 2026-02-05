<?php
// 取得系統公告資料庫中所有資料放到系統列表中
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

try {
    $sql = "SELECT * FROM announcements ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    // 如果沒資料，回傳空陣列 [] 而不是報錯
    echo json_encode($data ?: [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "伺服器錯誤",
        "message" => $e->getMessage()
    ]);
}
