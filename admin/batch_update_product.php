<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

// 格式預想: { 'ids': [1, 2, 3], 'action': 'on' }
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if(empty($data['ids']) || !is_array($data['ids']) || empty($data['action'])) {
  http_response_code(400);
  echo json_encode(["error" => "參數錯誤，請選擇商品"]);
  exit;
}

$ids = $data['ids'];
$action = $data['action']; // 'on' 或 'off'

try {
  $isOnShelf = ($action === 'on') ? 1 : 0;


  // 計算使用者到底勾選了幾個商品，使用動態計算的方式產生佔位符"?"，之後再execute填進去商品id
  // rtrim(..., ',') 會把最後的逗號去掉 -> "?,?,?"
  $placeHolder = rtrim(str_repeat('?,', count($ids)), ',');

  $sql = "UPDATE products SET is_on_shelf = ? WHERE product_id IN ($placeHolder)";
  $stmt = $pdo->prepare($sql);

  // execute() 需要一個陣列
  // 建立陣列參數
  $params = array_merge([$isOnShelf], $ids);
  $stmt->execute($params);

  echo json_encode([
    'success' => true,
    'message' => '批次更新成功！共更新了 ' . $stmt->rowCount() . ' 筆商品'
  ]);

} catch(Exception $e) {
  http_response_code(500);
  echo json_encode([
    "error" => $e->getMessage()
  ]);
}
?>