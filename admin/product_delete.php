<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];

try {
  // 檢查 order_items 有沒有這個商品(有沒有被買過)
  $check = $pdo->prepare("SELECT count(*) FROM order_items WHERE product_id = ?");
  $check->execute([$id]);
  if($check->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => '此商品已有訂單，無法刪除，請改用下架']);
    exit;
  }

  // 執行刪除
  $sql = "DELETE FROM products WHERE product_id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$id]);

  echo json_encode(['success' => true, 'message' => '刪除成功']);

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => '刪除失敗']);
}