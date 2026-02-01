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
  
    if($product) {
      // 先將字串轉陣列，vue才能用
      $product['features'] = json_decode($product['features']);
      $product['details'] = json_decode($product['details']);
  
      // 抓照片
      $sql_gallery = "SELECT image_id, large_url AS large, small_url AS small FROM product_gallery WHERE product_id = ?";
      $stmt_gallery = $pdo->prepare($sql_gallery);
      $stmt_gallery->execute([$id]);
      $gallery_list = $stmt_gallery->fetchAll(PDO::FETCH_ASSOC);
  
      // 轉陣列、抓資料完畢，塞回商品資料中
      $product['gallery'] = $gallery_list;
  
      echo json_encode($product);
    } else {
      echo json_encode(null);
    }
  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
  }
} else {
  echo json_encode(["error" => "Invalid ID"]);
}

  
?>