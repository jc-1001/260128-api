<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

// 需求
// 一般狀態切換(備貨中 / 已出貨)
// 取消訂單(已取消) - 涉及到"庫存量回補"

// 接收前端傳來的json資料(包含 id, status, note)
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// 檢查是否有抓到必要欄位資料
if(empty($data['id']) || empty($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => '缺少必要參數']);
    exit;
}

$id = intval($data['id']);
$status = $data['status'];
$note = $data['note'] ?? ''; // 取消訂單後，note會加上"取消原因"(選填)

try {
  // 取消訂單(庫存需回補)
  if($status === '已取消') {
    $pdo->beginTransaction();

    // 改狀態
    // 把取消原因 ($note) 加到備註欄位後面 (CONCAT)
    // CONCAT: 把兩個字串連接在一起
    $sql_update = "UPDATE orders SET order_status = ?, note = CONCAT(note, ?) WHERE order_id = ?";
    $stmt = $pdo->prepare($sql_update);
    // \n是換行符號
    $stmt->execute([$status, "\n[取消原因]: $note", $id]);

    // 查出這張訂單買了哪些商品、買了幾個
    $sql_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$id]);

    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // 加回庫存量
    $sql_restock = "UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?";
    $stmt_restock = $pdo->prepare($sql_restock);

    // 跑迴圈，把每一個商品的庫存加回去
    foreach ($items as $item) {
      // 執行庫存回補
      // 帶入上方需要的"數量"及"商品id"
      $stmt_restock->execute([$item['quantity'], $item['product_id']]);
    }

    $pdo->commit();
  }

  // 一般狀態更新
  else {
    $sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $id]);
  }

  echo json_encode([
    'success' => true,
    'message' => '狀態更新成功!'
  ]);

} catch(Exception $e) {
  // 訂單取消失敗的話，交易要rollback()
  if($pdo->inTransaction) {
    $pdo->rollback();
  }

  http_response_code(500);
  echo json_encode([
    "error" => $e->getMessage()
  ]);
}
?>