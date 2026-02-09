<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';
require_once __DIR__ . '/../notifications/send_notification.php';

try {
  $json = file_get_contents('php://input');
  $data = json_decode($json, true);

  // 檢查有沒有收到東西
  if(empty($data)) {
    throw new Exception("沒有訂單資料");
  }

  // 啟動"交易模式(Transaction)"
  $pdo->beginTransaction();

  // 建立訂單主體 ==============================

  // 1.訂單編號 (範例: ORD-20260130-8888)
  // date('YmdHis') = 年月日時分秒
  // rand(100, 999) = 隨機三碼 (避免同一秒有人同時下單重複)
  $order_number = 'ORD-'.date('YmdHis').'-'.rand(100,999);

  $member_id = isset($data['member_id']) ? intval($data['member_id']) : 0;

  // 準備SQL
  $sql_order = "INSERT INTO orders(
    order_number, member_id, recipient_name, recipient_phone, recipient_email, recipient_address, note, payment_type, invoice_type, invoice_info, shipping_fee, discount, total, created_at, order_status, is_paid) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), '訂單成立', 0)";

  $stmt_order = $pdo->prepare($sql_order);
  $stmt_order->execute([
    $order_number,
    $member_id,
    $data['recipient_name'],
    $data['recipient_phone'],
    $data['recipient_email'],
    $data['recipient_address'],
    $data['note'] ?? '',
    $data['payment_type'],
    $data['invoice_type'] ?? null,
    $data['invoice_info'] ?? null,
    $data['shippingFee'],
    $data['discount'],
    $data['total']
  ]);

  // 拿到最新的order_id，之後才能填進order_items 和 point_transactions
  $new_order_id = $pdo->lastInsertId();

  // 準備通知
  $title = "訂單成立通知";
  $content = "您的訂單 {$order_number} 已成功建立！我們將盡快為您安排出貨。";

  // 發送通知 ($member_id 應該是妳從 session 或 token 拿到的當前使用者 ID)
  sendNotification($pdo, $member_id, $title, $content);

  // 處理每一個商品(訂單明細 + 扣庫存) ======================

  $sql_item = "INSERT INTO order_items(
    order_id, product_id, product_name, product_spec, price, quantity
  ) VALUES (?, ?, ?, ?, ?, ?)";

  // 準備寫入明細的 SQL
  $stmt_item = $pdo->prepare($sql_item);

  // 準備扣庫存的 SQL
  $sql_stock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?"; 
  $stmt_stock = $pdo->prepare($sql_stock);

  // 跑迴圈抓購物車的每個商品
  foreach ($data['items'] as $item) {

    // 寫入 order_items (存成當前的商品快照)
    $stmt_item->execute([
      $new_order_id,
      $item['id'],
      $item['title'],
      $item['spec'],
      $item['price'],
      $item['qty'],
    ]);
    // 找到購買商品，扣除庫存量
    $stmt_stock->execute([
      $item['qty'],
      $item['id']
    ]);
  }

  // 處理積分折抵 ==============================
  // 確認是否有使用積分
  if( isset($data['discount']) && $data['discount'] > 0) {
    $sql_point = "INSERT INTO point_transactions(
      member_id, order_id, source, points_change, created_at
    ) VALUES (
      ?, ?, 3, ?, NOW()
    )";
    $stmt_point = $pdo->prepare($sql_point);

    // 消耗積分計算(10點折1元)，以折抵金額往回推
    $points_used = -10 * $data['discount']; // 記得是負數!

    $stmt_point->execute([
      $member_id,
      $new_order_id,
      $points_used
    ]);

  }
  
  // 提交交易(Commit) ==============================
  $pdo->commit();

  // 要回傳給前端的資料們，回傳內容自訂，方便前端判斷訂單是否建立成功
  echo json_encode([
    "success" => true, // 用來判斷是否成功
    "message" => '訂單建立成功', // 提示訊息
    "orderId" => $new_order_id,
    "orderNumber" => $order_number
  ]);

  } catch (Exception $e) {

  // 失敗返回交易(Rollback) ==============================
  if ($pdo->inTransaction()) {
    $pdo->rollback();
  }

  http_response_code(500);
  // 回傳給前端的錯誤訊息
  echo json_encode(
    ["error" => "訂單建立失敗: " . $e->getMessage()]
  );
  }
?>  