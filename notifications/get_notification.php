<?php

require_once __DIR__ . '/../common/cors.php';

require_once __DIR__ . '/../common/connect_cjd102g1.php';

$member_id = isset($_GET['mid']) ? intval($_GET['mid']) : 0;

if ($member_id <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "未提供有效的會員 ID"]);
    exit;
}

try {
    $sql = "SELECT * FROM notifications WHERE member_id = :mid AND is_read = 0 ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['mid'=> $member_id]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 確保輸出的每個 is_read 都是布林值
    foreach ($data as &$item) {
        $item['is_read'] = (bool)$item['is_read'];
    }

    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
