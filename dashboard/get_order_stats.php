<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

header("Content-Type: application/json; charset=UTF-8");

// 只允許 GET 請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode([
    'success' => false,
    'message' => '只允許 GET 請求'
  ], JSON_UNESCAPED_UNICODE);
  exit();
}

try {
  // 🔥 1. 今日新增訂單數量
  $sqlToday = "
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE DATE(created_at) = CURDATE()
    ";
  $stmtToday = $pdo->query($sqlToday);
  $ordersToday = (int)$stmtToday->fetch(PDO::FETCH_ASSOC)['count'];

  // 🔥 2. 待處理訂單數量（不是配送中、已完成、已取消的訂單）
  $sqlPending = "
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE order_status NOT IN ('配送中', '已完成', '已取消')
    ";
  $stmtPending = $pdo->query($sqlPending);
  $ordersPending = (int)$stmtPending->fetch(PDO::FETCH_ASSOC)['count'];

  // 成功回應
  http_response_code(200);
  echo json_encode([
    'success' => true,
    'data' => [
      'orders_today' => $ordersToday,
      'orders_pending' => $ordersPending,
    ],
    'message' => '成功取得訂單統計資料'
  ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  // 資料庫錯誤
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => '資料庫錯誤',
    'error' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
