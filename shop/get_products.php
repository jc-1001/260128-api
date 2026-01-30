<?php
// 1. 設定權限 (CORS) - 讓 Vue 可以跨網域抓資料
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  exit(0); 
}

require_once '../common/connect_cjd102g1.php';

try {
  $sql = "SELECT * FROM products WHERE is_on_shelf = 1";
  // $stmt (Statement)
  $stmt = $pdo->prepare($sql);
  $stmt->execute();

  // PDO (PHP Data Objects)，資料庫連線工具，可接不同的資料庫
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($products);
  // 抓錯誤，將錯誤存進"$e"變數
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["error" => $e->getMessage()]);
}

?>