<?php

require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';
require_once __DIR__ . '/../linepay/LinePayService.php';

// 接收前端傳來的"訂單編號"(對應資料庫的"order_number")
$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['orderId'];

if(!$orderId) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => '缺少訂單編號']);
  exit;
}

try {
  // 要去資料庫查訂單最後最後的"總金額"(就是總付款額)
  $sql = "SELECT order_id, order_number, total, order_status, is_paid FROM orders WHERE order_number = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$orderId]);

  $order = $stmt->fetch(PDO::FETCH_ASSOC);

  if(!$order) {
    throw new Exception('找不到此訂單');
  }

  // 避免重複付款(如果已經付過了，就不要再發請求)
  if($order['is_paid'] == 1 || $order['order_status'] === '備貨中') {
    throw new Exception("此訂單已進入付款流程或已完成");
  }

  // 設定檔讀一次就好，不用一直require
  $config = require __DIR__ . '/../common/config_linepay.php';
  // 呼叫LINE Pay
  $linePay = new LinePayService($config);

  // 要傳送給LINE Pay的資料
  $payload = [
    'amount' => (int)$order['total'],
    'currency' => 'TWD',
    'orderId' => $order['order_number'],
    'packages' => [
      [
        'id' => 'PKG-' . $order['order_number'],
        'amount' => (int)$order['total'],
        'products' => [
          [
            'name' => 'Unicare 訂單 #' . $order['order_number'],
            'quantity' => 1,
            'price' => (int)$order['total'],
          ]
        ]
      ] 
    ],
    'redirectUrls' => [
      'confirmUrl' => $config['return_url'],
      'cancelUrl' => $config['cancel_url'],
    ]
  ];

  // 發送請求
  $result = $linePay->request('/v3/payments/request', $payload);

  if($result['success']) {
    // 把 LINE Pay 回傳的 transactionId 存回資料庫
    $transactionId = $result['data']['transactionId'];
    
    $updateSql = "UPDATE orders SET transaction_id = ? WHERE order_id = ?";
    $pdo->prepare($updateSql)->execute([$transactionId, $order['order_id']]);

    // 將付款網址回傳給前端，讓前端跳轉頁面
    echo json_encode([
      'success' => true,
      'paymentUrl' => $result['data']['paymentUrl']['web'],
      'transactionId' => $transactionId
    ]);
  } else {
    // LINE Pay 拒絕了請求
    throw new Exception("LINE Pay 請求失敗: " . $result['message']);
  }


} catch (Throwable $e) { // 改成 Throwable，可以抓到更多種錯誤
  http_response_code(500);
  echo json_encode([
    'success' => false, 
    'message' => $e->getMessage()
  ]);
}