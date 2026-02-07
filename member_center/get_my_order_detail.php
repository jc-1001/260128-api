<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

// 查主檔：去 orders 拿收件人、總金額、狀態
// 查明細：去 order_items 拿這張單買了哪幾樣東西

// 取得網址上的 id 參數
$id = isset($_GET['id']) ? intval($_GET['id']) : 0; 

// $member_id = 1;
// 會員大改 =============
$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;

if( $id > 0 ) {
  try {

  // 抓取訂單主檔
    $sql_order = "SELECT * FROM orders WHERE order_id = ? AND member_id = ?";
    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->execute([$id, $member_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);

    if(!$order) {
      echo json_encode(null);
      exit;
    }

    // 抓取訂單細節
    // 這邊要連結 order_items 和 products 兩個表格
    $sql_items = "SELECT oi.product_name AS title, oi.price, oi.quantity AS qty, oi.product_spec AS spec, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id  = p.product_id WHERE oi.order_id = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$id]);

    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 組裝詳情頁資料
    $response = [
      'db_id' => $order['order_id'],
      'id' => $order['order_number'],
      'date' => $order['created_at'],
      'status' => $order['order_status'],
      
      'user' => [
        'name' => $order['recipient_name'],
        'phone' => $order['recipient_phone'],
        'address' => $order['recipient_address'],
      ],
      'paymentType' => $order['payment_type'],
      'isPaid' => $order['is_paid'],
      'invoiceType' => $order['invoice_type'],

      'items' => $items,

      'shippingFee' => $order['shipping_fee'],
      'discount' => $order['discount'],
      'total' => $order['total']
    ];

    // 回傳結果
    echo json_encode($response);
  
  } catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
      "error" => $e->getMessage()
    ]);
  }
} else {
  echo json_encode(["error" => "Invalid ID"]);
}

?>