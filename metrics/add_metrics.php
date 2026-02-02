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
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // 🔥 根據實際資料庫結構定義
    $metricsConfig = [
        'weight' => [
            'table' => 'weight_logs',
            'id_field' => 'weight_log_id',
            'fields' => ['weight'],
            'time_field' => 'measured_at',
            'validation' => function($data) {
                if (!isset($data['weight'])) return '缺少體重數值';
                if ($data['weight'] <= 0 || $data['weight'] > 500) return '體重數值不合理';
                return null;
            }
        ],
        'blood_oxygen' => [
            'table' => 'blood_oxygen_logs',
            'id_field' => 'oximetry_log_id',
            'fields' => ['oxygen_saturation'],
            'time_field' => 'measured_at',
            'validation' => function($data) {
                if (!isset($data['oxygen_saturation'])) return '缺少血氧數值';
                if ($data['oxygen_saturation'] < 0 || $data['oxygen_saturation'] > 100) return '血氧數值不合理';
                return null;
            }
        ],
        'blood_sugar' => [
            'table' => 'blood_sugar_logs',
            'id_field' => 'glucose_log_id',
            'fields' => ['glucose_value'],
            'time_field' => 'measured_at',
            'validation' => function($data) {
                if (!isset($data['glucose_value'])) return '缺少血糖數值';
                if ($data['glucose_value'] < 0 || $data['glucose_value'] > 600) return '血糖數值不合理';
                return null;
            }
        ],
        'heart_rate' => [
            'table' => 'blood_pressure_logs',
            'id_field' => 'bp_log_id',
            'fields' => ['heart_rate', 'systolic_pressure', 'diastolic_pressure'],  // 🔥 需要血壓數據
            'time_field' => 'measured_at',
            'validation' => function($data) {
                if (!isset($data['heart_rate'])) return '缺少心律數值';
                if ($data['heart_rate'] < 30 || $data['heart_rate'] > 250) return '心律數值不合理';
                // 🔥 心律存在血壓表中，需要同時提供血壓數值（可以設為 0 或預設值）
                if (!isset($data['systolic_pressure'])) $data['systolic_pressure'] = 0;
                if (!isset($data['diastolic_pressure'])) $data['diastolic_pressure'] = 0;
                return null;
            }
        ],
        'blood_pressure' => [
            'table' => 'blood_pressure_logs',
            'id_field' => 'bp_log_id',
            'fields' => ['systolic_pressure', 'diastolic_pressure', 'heart_rate'],  // 🔥 加入心律
            'time_field' => 'measured_at',
            'validation' => function($data) {
                if (!isset($data['systolic_pressure']) || !isset($data['diastolic_pressure'])) return '缺少血壓數值';
                if ($data['systolic_pressure'] < 50 || $data['systolic_pressure'] > 250) return '收縮壓數值不合理';
                if ($data['diastolic_pressure'] < 30 || $data['diastolic_pressure'] > 150) return '舒張壓數值不合理';
                // 🔥 如果沒提供心律，設為 0
                if (!isset($data['heart_rate'])) $data['heart_rate'] = 0;
                return null;
            }
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

    if (!isset($data['member_id']) || !isset($data['measured_at'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '缺少必要欄位 (member_id, measured_at)'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $validationError = $config['validation']($data);
    if ($validationError) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $validationError], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $measured_at = $data['measured_at'];
    $datetime = DateTime::createFromFormat('Y-m-d H:i', $measured_at);
    if (!$datetime) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '日期時間格式錯誤 (應為 YYYY-MM-DD HH:mm)'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $member_id = (int)$data['member_id'];
    $fields = array_merge(['member_id'], $config['fields'], [$config['time_field']]);
    $placeholders = array_map(function($field) { return ":$field"; }, $fields);

    $sql = "INSERT INTO {$config['table']} (" . implode(', ', $fields) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";

    $params = ['member_id' => $member_id];
    foreach ($config['fields'] as $field) {
        $params[$field] = $data[$field];
    }
    $params[$config['time_field']] = $measured_at;

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        $lastInsertId = $pdo->lastInsertId();
        $responseData = [
            $config['id_field'] => $lastInsertId,
            'member_id' => $member_id
        ];
        foreach ($config['fields'] as $field) {
            $responseData[$field] = $data[$field];
        }
        $responseData['recorded_at'] = $measured_at;

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => '新增成功',
            'data' => $responseData
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