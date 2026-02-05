<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';
require_once __DIR__ . '/../linepay/LinePayService.php';

$data = json_decode(file_get_contents('php://input'), true);
$transactionId = $data['transactionId'];

if(!$transactionId) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => '缺少交易編號']);
  exit;
}

try {
  $sql = "SELECT * FROM orders WHERE transaction_id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$transactionId]);
  $order = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$order) {
    throw new Exception("找不到對應的訂單");
  }

  if ($order['is_paid'] == 1) {
    echo json_encode(['success' => true, 'message' => '訂單已付款']);
    exit;
  }

  // 2. 呼叫 LINE Pay Confirm API (真正扣款)
  $config = require __DIR__ . '/../common/config_linepay.php';
  $linePay = new LinePayService($config);

  // Confirm API 需要帶金額 currency
  $payload = [
    'amount' => (int)$order['total'],
    'currency' => 'TWD',
  ];

  $result = $linePay->request("/v3/payments/$transactionId/confirm", $payload);

  if ($result['success']) {
    // 扣款成功，更新資料庫狀態
    $updateSql = "UPDATE orders SET is_paid = 1 WHERE order_id = ?";
    $pdo->prepare($updateSql)->execute([$order['order_id']]);

    echo json_encode(['success' => true, 'message' => '付款成功']);
  } else {
    throw new Exception("LINE Pay 確認失敗: " . $result['message']);
  }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}