<?php
// 首頁取得最新狀態 API

require_once __DIR__ . '/../common/cors.php';

require_once __DIR__ . '/../common/connect_cjd102g1.php';


// 1. 強制設定時區（確保與台灣時間一致）
date_default_timezone_set('Asia/Taipei');
$today = date('Y-m-d');
$stats = [
    '體重' => '--',
    '血氧' => '--',
    '血糖' => '--',
    '血壓' => '--',
    '心律' => '--',
    '身高' => '0'
];
$member_id = 1;

try {
    // 取得會員身高
    $stmtHeight = $pdo->prepare("SELECT height FROM members WHERE member_id = :mid");
    $stmtHeight->execute(['mid' => $member_id]);
    if ($h = $stmtHeight->fetchColumn()) $stats['身高'] = (float)$h;
    
    // 2. 使用參數化查詢並帶入 $today 變數
    // 血壓與心律
    $stmt = $pdo->prepare("SELECT systolic_pressure, diastolic_pressure, heart_rate FROM blood_pressure_logs 
                           WHERE member_id = :mid AND DATE(measured_at) = :today 
                           ORDER BY measured_at DESC LIMIT 1");
    $stmt->execute(['mid' => $member_id, 'today' => $today]);
    if ($res = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['血壓'] = "{$res['systolic_pressure']}/{$res['diastolic_pressure']}";
        $stats['心律'] = (string)$res['heart_rate']; // 這行確保了心律卡片有資料
    }

    // 體重 (float 轉換可去掉資料庫小數點後多餘的 0)
    $stmt = $pdo->prepare("SELECT weight FROM weight_logs 
                       WHERE member_id = :mid AND DATE(measured_at) = :today 
                       ORDER BY measured_at DESC LIMIT 1");
    $stmt->execute(['mid' => $member_id, 'today' => $today]);
    if ($val = $stmt->fetchColumn()) $stats['體重'] = (string)(float)$val;

    // 血氧
    $stmt = $pdo->prepare("SELECT oxygen_saturation FROM blood_oxygen_logs 
                       WHERE member_id = :mid AND DATE(measured_at) = :today 
                       ORDER BY measured_at DESC LIMIT 1");
    $stmt->execute(['mid' => $member_id, 'today' => $today]);
    if ($val = $stmt->fetchColumn()) $stats['血氧'] = (string)$val;

    // 血糖
    $stmt = $pdo->prepare("SELECT glucose_value FROM blood_sugar_logs 
                       WHERE member_id = :mid AND DATE(measured_at) = :today 
                       ORDER BY measured_at DESC LIMIT 1");
    $stmt->execute(['mid' => $member_id, 'today' => $today]);
    if ($val = $stmt->fetchColumn()) $stats['血糖'] = (string)$val;

    echo json_encode($stats);
} catch (PDOException $e) {
    http_response_code(500);
    // 把具體的錯誤訊息傳給前端
    echo json_encode(["err" => "資料庫查詢失敗: " . $e->getMessage()]);
}
