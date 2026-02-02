<?php
// 更新資料庫(更新系統公告編輯頁面資料表內容)
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

//取得網址上的 id (從 axios.post 的 URL 參數傳入)
$id = $_GET['id'] ?? null;

//取得前端傳來的 JSON Payload
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$id) {
    echo json_encode(["success" => false, "message" => "缺少公告 ID"]);
    exit;
}

if (!$data) {
    echo json_encode(["success" => false, "message" => "接收不到修改資料"]);
    exit;
}

try {
    // SQL語法更新
    $sql = "UPDATE announcements SET 
                title = :title, 
                announcement_type = :type, 
                content = :content, 
                start_at = :start_at, 
                end_at = :end_at, 
                status = :status,
                updated_at = NOW()
            WHERE announcement_id = :id";

    $stmt = $pdo->prepare($sql);
    
    //執行綁定與更新
    $stmt->execute([
        'title'    => $data['title'],
        'type'     => $data['announcement_type'],
        'content'  => $data['content'],
        'start_at' => $data['start_at'], // YYYY-MM-DD
        'end_at'   => $data['end_at'],   // YYYY-MM-DD 或 NULL
        'status'   => $data['status'],   // 'upload' 或 'draft'
        'id'       => $id
    ]);

    //回傳結果給 Vue
    echo json_encode([
        "success" => true, 
        "message" => "公告修改成功"
    ]);

} catch (Exception $e) {
    // 如果 SQL 報錯（例如欄位名打錯），會進到這裡
    echo json_encode([
        "success" => false, 
        "message" => "資料庫更新失敗: " . $e->getMessage()
    ]);
}