<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

// 接收前端傳來的資料
$data = json_decode(file_get_contents('php://input'), true);
$member_id = isset($data['member_id']) ? intval($data['member_id']) : 0;

// 設定來源代碼：4 代表「有獎徵答」
$source_code = 4;

try {
    if ($member_id === 0) {
        throw new Exception("請先登入會員");
    }

    // 檢查今日是否已領取
    $sql_check = "SELECT * FROM point_transactions WHERE member_id = ? AND source = ? AND DATE(created_at) = CURDATE()";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$member_id, $source_code]);

    if ($stmt_check->rowCount() > 0) {
        echo json_encode([
            'success' => false,
            'message' => '今日已經挑戰過囉！'
        ]);
        exit;
    }

    // 隨機產生積分
    $reward_points = rand(10, 60);

    $sql_insert = "INSERT INTO point_transactions(member_id, source, points_change, created_at) VALUES (?, ?, ?, NOW())";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([$member_id, $source_code, $reward_points]);

    echo json_encode([
        'success' => true,
        'message' => "挑戰成功！獲得 {$reward_points} 點",
        'points' => $reward_points
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>