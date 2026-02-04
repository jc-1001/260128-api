<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id > 0) {
  try {
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
  
    if(!$product) {
      echo json_encode(null);
      exit;
    }

    // 抓詳情頁圖片
    $sql_gallery = "SELECT * FROM product_gallery WHERE product_id = ?";
    $stmt_gallery = $pdo->prepare($sql_gallery);
    $stmt_gallery->execute([$id]);

    $gallery = $stmt_gallery->fetchAll(PDO::FETCH_ASSOC);

    // 資料加工(decode json)
    // 資料庫裡是字串 '["特色1"]' -> 轉成 PHP 陣列 -> 轉成 JSON 給前端
    $product['features'] = json_decode($product['features'] ?? '[]', true);
    $product['details'] = json_decode($product['details'] ?? '{}', true);

    $product['gallery'] = $gallery;
  
    echo json_encode($product);
  
  } catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
      "error" => $e->getMessage()
    ]);
  }
} else {
    echo json_encode(null);
}

?>