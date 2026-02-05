<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';


try {
  $sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.product_id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute();

  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $productList = [];

  foreach ($products as $product) {
    $productList[] = [
      'id' => $product['product_id'],
      'name' => $product['title'],
      'category' => $product['category_name'],
      'price' => $product['price'],
      'stock' => $product['stock_quantity'],
      'isOnShelf' => $product['is_on_shelf'] == 1, // 強制轉成 Boolean，避免不小心抓到"字串"型別，影響判斷結果 ex. if('0') 是 true，非空字串在 JS 裡都是 True
      'image' => $product['image'],
    ];
  }

  echo json_encode([
    'success' => true,
    'data' => $productList
  ]);

} catch(Exception $e) {
  http_response_code(500);
  echo json_encode([
    "error" => $e->getMessage()
  ]);
}
?>