<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';
// 設定回傳格式為 JSON
header('Content-Type: application/json');
// 接收 Vue 傳來的 member_id
$member_id = isset($_GET['member_id']) ? $_GET['member_id'] : 1;
try {
    $sql = "SELECT * FROM Diet_Logs WHERE member_id = :member_id ORDER BY meal_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id]);
    $allRecords = [];
    // 獲取所有資料
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $date = $row['meal_date'];
        if (!isset($allRecords[$date])) {
            $allRecords[$date] = [];
        }
        $allRecords[$date][] = [
            "diet_log_id"    => $row['diet_log_id'],
            "member_id"      => $row['member_id'],
            "meal_date"      => $row['meal_date'],
            "meal_time"      => $row['meal_time'],
            "meal_type"      => $row['meal_type'],
            "food_image_url" => $row['food_image_url'],
            "description"    => $row['description'],
            "created_at"     => $row['created_at'],
            "updated_at"     => $row['updated_at']
        ];
    }
    // 正確輸出 JSON
    echo json_encode($allRecords);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "查詢失敗: " . $e->getMessage()]);
}
?>