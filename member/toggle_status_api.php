<?php
// toggle_status_api.php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

// 預檢請求 (Preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}


// 接收 JSON 資料
$data = json_decode(file_get_contents("php://input"), true);
if (array_key_exists('member_id', $data) && array_key_exists('new_status', $data))  {
    try {
        $sql = "UPDATE members SET account_status = :new_status WHERE member_id = :member_id";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':new_status' => (int)$data['new_status'],
            ':member_id'  => (int)$data['member_id']
        ]);

        if ($result) {
            echo json_encode(["success" => true, "message" => "更新成功"]);
        } else {
            echo json_encode(["success" => false, "message" => "資料庫更新失敗"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "資料庫錯誤: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "參數不足，請確認 member_id 與 new_status"]);
}
?>