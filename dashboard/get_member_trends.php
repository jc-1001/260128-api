<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '只允許 GET 請求'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    $period = $_GET['period'] ?? 'month'; // month, quarter, year
    
    // 根據不同期間設定天數
    $days = 30; // 預設30天
    if ($period === 'month') {
        $days = 30;
    } elseif ($period === 'quarter') {
        $days = 90;
    } elseif ($period === 'year') {
        $days = 365;
    }
    
    // 查詢每日新增會員數
    $sql = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM members
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$days]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 格式化數據
    $labels = [];
    $values = [];
    
    foreach ($data as $row) {
        // 根據天數範圍決定日期格式
        if ($days <= 30) {
            // 30天：顯示 MM/DD
            $labels[] = date('m/d', strtotime($row['date']));
        } elseif ($days <= 90) {
            // 90天：顯示 MM/DD
            $labels[] = date('m/d', strtotime($row['date']));
        } else {
            // 365天：顯示 YYYY/MM
            $labels[] = date('Y/m', strtotime($row['date']));
        }
        $values[] = (int)$row['count'];
    }
    
    // 如果365天，需要按月聚合
    if ($days === 365) {
        $monthlyData = [];
        foreach ($data as $row) {
            $month = date('Y/m', strtotime($row['date']));
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = 0;
            }
            $monthlyData[$month] += (int)$row['count'];
        }
        
        $labels = array_keys($monthlyData);
        $values = array_values($monthlyData);
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'values' => $values
        ],
        'message' => '成功取得會員趨勢資料'
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '資料庫錯誤',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>