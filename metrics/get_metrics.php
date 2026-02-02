<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => '只允許 GET 請求'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $type = $_GET['type'] ?? '';
    $member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 1;

    if ($member_id < 1) {
        http_response_code(400);
        echo json_encode(['error' => true, 'message' => '無效的會員 ID'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 🔥 根據實際資料庫結構定義
    $metricsConfig = [
        'weight' => [
            'table' => 'weight_logs',
            'id_field' => 'weight_log_id',
            'value_fields' => ['weight'],
            'time_field' => 'measured_at'
        ],
        'blood_oxygen' => [
            'table' => 'blood_oxygen_logs',
            'id_field' => 'oximetry_log_id',
            'value_fields' => ['oxygen_saturation'],
            'time_field' => 'measured_at'
        ],
        'blood_sugar' => [
            'table' => 'blood_sugar_logs',
            'id_field' => 'glucose_log_id',
            'value_fields' => ['glucose_value'],
            'time_field' => 'measured_at'
        ],
        'heart_rate' => [
            'table' => 'blood_pressure_logs',  // 🔥 心律在血壓表中
            'id_field' => 'bp_log_id',
            'value_fields' => ['heart_rate'],
            'time_field' => 'measured_at'
        ],
        'blood_pressure' => [
            'table' => 'blood_pressure_logs',
            'id_field' => 'bp_log_id',
            'value_fields' => ['systolic_pressure', 'diastolic_pressure'],
            'time_field' => 'measured_at'
        ]
    ];

    if (!isset($metricsConfig[$type])) {
        http_response_code(400);
        echo json_encode([
            'error' => true, 
            'message' => '無效的指標類型：' . $type,
            'valid_types' => array_keys($metricsConfig)
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $config = $metricsConfig[$type];
    $selectFields = [$config['id_field'], 'member_id'];
    $selectFields = array_merge($selectFields, $config['value_fields']);
    
    $sql = "SELECT 
                " . implode(', ', $selectFields) . ",
                DATE_FORMAT({$config['time_field']}, '%Y-%m-%d %H:%i:%s') as recorded_at
            FROM {$config['table']} 
            WHERE member_id = :member_id 
            ORDER BY {$config['time_field']} DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_id' => $member_id]);
    $results = $stmt->fetchAll();

    if (empty($results)) {
        $results = [];
    }

    http_response_code(200);
    echo json_encode($results, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => '資料庫查詢錯誤',
        'details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>