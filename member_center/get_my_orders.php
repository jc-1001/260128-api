<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';


$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;

try {
  $sql = "SELECT * FROM orders WHERE member_id = ? ORDER BY created_at DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$member_id]);
  
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 將資料改成"對應前端名稱"回傳
  $formatted_orders = [];

  foreach ($orders as $row) {
    $order_id = $row['order_id'];

    $sql_items = "SELECT oi.product_id, oi.product_name AS title, oi.product_spec AS spec, oi.quantity AS qty, oi.price, p.image FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    $formatted_orders[] = [
      'id'     => $row['order_number'], 
      'db_id'  => $row['order_id'],
      'status' => $row['order_status'],
      'customer' => $row['recipient_name'], 
      'date'   => $row['created_at'],   
      'total'  => $row['total'],        
      'items'  => $items      
    ];
  }

  // 回傳加工後的json 
  echo json_encode($formatted_orders);

} catch(Exception $e) {
  http_response_code(500);
  echo json_encode([
    "error" => $e->getMessage()
  ]);
}
?>