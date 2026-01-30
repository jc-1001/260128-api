<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS"); // 這裡改用 POST，因為我們要傳資料進來
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../common/connect_cjd102g1.php';

try {





} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["error" => "訂單建立失敗: " . $e->getMessage()]);
}


?>