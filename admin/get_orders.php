<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

try {
  $sql = "SELECT * FROM orders ORDER BY created_at DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute();
  
  $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 將資料改成"對應前端名稱"回傳
  $formatted_orders = [];

  foreach ($orders as $row) {
    $formatted_orders[] = [
      // 前端 key => 資料庫 value
      'order_id' => $row['order_number'],
      'db_id' => $row['order_id'],       
      'status' => $row['order_status'],   
      'customer' => $row['recipient_name'], 
      'order_time' => $row['created_at'],     
      'amount' => $row['total']           
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