<?php
// 已清除bug

require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

$id = $_GET['id'] ?? $_REQUEST['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false, "message" => "沒收到 ID"]);
    exit;
}

try {
    // 執行刪除
    $sql = "DELETE FROM Announcements WHERE announcement_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

    echo json_encode(["success" => true]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false, 
        "message" => "資料庫錯誤: " . $e->getMessage()
    ]);
}