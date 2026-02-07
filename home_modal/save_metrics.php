<?php
// 接收前端傳來的 activeMetricKey 與數值，存入對應的資料表。
require_once __DIR__ . '/../common/cors.php';

// // 2. 重要：處理 Preflight (OPTIONS 請求)
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200); // 回傳 200 OK 給瀏覽器
//     exit;
// }
require_once __DIR__ . '/../common/connect_cjd102g1.php';

$data = json_decode(file_get_contents("php://input"), true);
// 基本檢查，避免空資料進入
if (!$data || !isset($data['type'])) {
    http_response_code(400);
    echo json_encode([
        "err" => "無效的請求資料",
        "received_id" => $member_id, 
        "received_type" => $type
    ]);
    exit;
}
$type = $data['type']; // weight, bloodOxygen, bloodSugar, bloodPressure
$val = $data['payload'];

$member_id = isset($data['member_id']) ? intval($data['member_id']) : 0;

if (!$data || !isset($data['type']) || $member_id <= 0) {
    http_response_code(400);
    echo $data;
    echo json_encode(["err" => "無效的請求資料或未提供會員 ID"]);
    exit;
}

try {
    if ($type === 'bloodPressure') {
        $sql = "INSERT INTO blood_pressure_logs (member_id, systolic_pressure, diastolic_pressure, heart_rate, measured_at) 
            VALUES (:mid, :systolic_pressure, :diastolic_pressure, :hr, :at)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'mid' => $member_id,
            'systolic_pressure' => $val['systolic_pressure'],
            'diastolic_pressure' => $val['diastolic_pressure'],
            'hr'  => $val['heartRate'],
            'at'  => $val['measured_at']
        ]);
    } else {
        $tableMap = [
            'weight'      => ['table' => 'weight_logs', 'col' => 'weight'], 
            'bloodOxygen' => ['table' => 'blood_oxygen_logs', 'col' => 'oxygen_saturation'], 
            'bloodSugar'  => ['table' => 'blood_sugar_logs', 'col' => 'glucose_value'] 
        ];
        if (!isset($tableMap[$type])) {
            throw new Exception("不支援的記錄類型: " . $type);
        }

        // 從設定檔中取出真正的字串
        $targetTable = $tableMap[$type]['table'];
        $targetCol = $tableMap[$type]['col'];

        // 使用變數拼接 SQL (注意：這裡用的是 $targetTable 和 $targetCol)
        $sql = "INSERT INTO $targetTable (member_id, $targetCol, measured_at) VALUES (:mid, :v, :at)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'mid' => $member_id,
            'v'   => $val['value'],
            'at'  => $val['measured_at']
        ]);
    }
    echo json_encode(["success" => true,"status" => "success"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["err" => $e->getMessage()]);
}
