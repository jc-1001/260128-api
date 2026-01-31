<?php
// toggle_status_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 處理預檢請求 (Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once '../db_config.php'; // 確保路徑指向 MAMP 設定

// 接收 JSON 資料
$data = json_decode(file_get_contents("php://input"), true);

// 檢查必要的參數是否存在
if (isset($data['member_id']) && isset($data['new_status'])) {
    try {
        $sql = "UPDATE members SET account_status = :new_status WHERE member_id = :member_id";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':new_status' => (int)$data['new_status'],
            ':member_id'  => (int)$data['member_id']
        ]);

        if ($result) {
            echo json_encode(["success" => true, "message" => "狀態更新成功"]);
        } else {
            echo json_encode(["success" => false, "message" => "資料庫更新未生效"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "資料庫錯誤: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "傳送的參數不足，請確認 member_id 與 new_status"]);
}
?>