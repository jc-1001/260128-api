<?php
// 查看系統公告的詳細資訊
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

$id = $_GET['id'] ?? null;

try {
    // 加上 WHERE 條件，只抓這一個 ID
    $sql = "SELECT a.*, adm.admin_name 
            FROM announcements a 
            INNER JOIN admins adm ON a.created_by_admin_id = adm.admin_id
            WHERE a.announcement_id = :id";
      
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $notice = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json; charset=utf-8');
    if ($notice) {
        echo json_encode(["success" => true, "data" => $notice], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["success" => false, "message" => "找不到該公告"], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}