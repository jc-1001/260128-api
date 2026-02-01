<?php
// 新增一筆新的系統公告到資料庫中
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

//取得前端傳來的 JSON Payload
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// 檢查是否有收到基本資料
if (!$data || !isset($data['title'])) {
    echo json_encode([
        "success" => false,
        "message" => "接收不到新增資料，請檢查前端格式"
    ]);
    exit;
}

try {
    // SQL 插入語法
    $sql = "INSERT INTO Announcements (
                created_by_admin_id, 
                title, 
                announcement_type, 
                content, 
                start_at, 
                end_at, 
                status, 
                created_at, 
                updated_at
            ) VALUES (
                :admin_id, 
                :title, 
                :type, 
                :content, 
                :start_at, 
                :end_at, 
                :status, 
                NOW(), 
                NOW()
            )";

    $stmt = $pdo->prepare($sql);

    // 執行綁定與新增
    $stmt->execute([
        'admin_id' => $data['created_by_admin_id'] ?? 1, // 預設為管理員 1
        'title'    => $data['title'],
        'type'     => $data['announcement_type'],
        'content'  => $data['content'],
        'start_at' => $data['start_at'], // yyyy-mm-dd
        'end_at'   => empty($data['end_at']) ? null : $data['end_at'], // 如果是空字串則存為 NULL
        'status'   => $data['status']    // 'upload' 或 'draft'
    ]);

    // 回傳成功訊息
    echo json_encode([
        "success" => true,
        "message" => "公告新增成功",
        "new_id"  => $pdo->lastInsertId() // 回傳新生成的公告 ID (選擇性)
    ]);

} catch (Exception $e) {
    // 如果 SQL 報錯（例如欄位名打錯），會進到這裡
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "資料庫新增失敗: " . $e->getMessage()
    ]);
}