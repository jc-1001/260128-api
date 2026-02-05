<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '只允許 POST 請求'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $type = $_GET['type'] ?? '';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '缺少記錄 ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 🔥 定義各指標的配置
    $metricsConfig = [
        'weight' => [
            'table' => 'weight_logs',
            'id_field' => 'weight_log_id',
            'fields' => ['weight'],
            'time_field' => 'measured_at'
        ],
        'blood_oxygen' => [
            'table' => 'blood_oxygen_logs',
            'id_field' => 'oximetry_log_id',
            'fields' => ['oxygen_saturation'],
            'time_field' => 'measured_at'
        ],
        'blood_sugar' => [
            'table' => 'blood_sugar_logs',
            'id_field' => 'glucose_log_id',
            'fields' => ['glucose_value'],
            'time_field' => 'measured_at'
        ],
        'heart_rate' => [
            'table' => 'blood_pressure_logs',
            'id_field' => 'bp_log_id',
            'fields' => ['heart_rate', 'systolic_pressure', 'diastolic_pressure'],
            'time_field' => 'measured_at'
        ],
        'blood_pressure' => [
            'table' => 'blood_pressure_logs',
            'id_field' => 'bp_log_id',
            'fields' => ['systolic_pressure', 'diastolic_pressure', 'heart_rate'],
            'time_field' => 'measured_at'
        ]
    ];

    if (!isset($metricsConfig[$type])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '無效的指標類型',
            'valid_types' => array_keys($metricsConfig)
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $config = $metricsConfig[$type];

    if (!isset($data['measured_at'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '缺少測量時間'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 🔥 組建 UPDATE SQL
    $updateFields = [];
    $params = [];
    
    foreach ($config['fields'] as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = :$field";
            $params[$field] = $data[$field];
        }
    }
    
    $updateFields[] = "{$config['time_field']} = :measured_at";
    $params['measured_at'] = $data['measured_at'];
    $params['id'] = $id;

    $sql = "UPDATE {$config['table']} 
            SET " . implode(', ', $updateFields) . "
            WHERE {$config['id_field']} = :id";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => '更新成功',
            'affected_rows' => $stmt->rowCount()
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => '更新失敗'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '資料庫錯誤',
        'details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>