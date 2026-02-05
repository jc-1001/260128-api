<?php
// 後台前面列表使用(跟編輯頁面沒有關係)
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

try{
    $sql = "SELECT 
                a.announcement_id,
                a.title,
                a.announcement_type,
                a.content,
                a.start_at,
                a.end_at,
                a.status,
                a.created_at,
                a.updated_at,
                a.created_by_admin_id,
                adm.admin_name
            FROM announcements a 
            INNER JOIN admins adm ON a.created_by_admin_id = adm.admin_id
            ORDER BY a.created_at DESC";
      
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($notices ?: [], JSON_UNESCAPED_UNICODE);

}catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "SQL執行錯誤" . $e->getMessage()
    ]);
}