<?php

require_once __DIR__ . '/../common/cors.php';

require_once __DIR__ . '/../common/connect_cjd102g1.php';

try {
    $sql = "SELECT * FROM Notifications WHERE member_id = 1 AND is_read = 0 ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

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
