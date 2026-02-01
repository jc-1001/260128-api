<?php
// PATCH 變更已讀狀態
require_once __DIR__ . '/../common/cors.php';

// 處理預檢請求 (Preflight Request)
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/../common/connect_cjd102g1.php';

if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    // 取得原始資料
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    $noteId = $input['notification_id'] ?? null;

    // 檢查是不是有個人通知資料表的主鍵(id)
    if (!$noteId) {
        echo json_encode(['error' => '缺少個人通知ID']);
        http_response_code(400);
        exit;
    }
    // 資料庫要變更is_read欄位，並對準該會員的ID
    // 執行更新
    try {
        $sql = "UPDATE Notifications SET is_read = 1 WHERE notification_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $noteId]);


        header('Content-Type: application/json'); // 確保回傳也是 JSON
        echo json_encode(['status' => 'success', 'message' => '訊息已讀']);

    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
        http_response_code(500);
    }
}
