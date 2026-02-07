<?php
// 此檔的header不太一樣，需允許 Content-Type 為 multipart/form-data
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../common/connect_cjd102g1.php';

try {
  $id = $_POST['id'] ?? '';
  $category_id = intval($_POST['category'] ?? 1);
  $title = $_POST['name'] ?? '';  
  $price = intval($_POST['price'] ?? 0);
  $stock_quantity = intval($_POST['stock'] ?? 0);
  $is_on_shelf = intval($_POST['status'] ?? 0);
  $tag = $_POST['tag'] ?? '';
  $description = $_POST['decs'] ?? ''; 
  $spec = $_POST['spec'] ?? '';
  $keywords = $_POST['keywords'] ?? '';

  // 接收json格式欄位資料 (features & details)
  // 前端會傳送 features[] 陣列 和 details[dosage] 等欄位
  // 要把它們轉成 JSON 字串存入資料庫

  $features = $_POST['features'] ?? [];
  // 如果 features 是字串(有些 axios 設定會這樣)，嘗試 decode，否則直接 encode
  if(is_string($features)) $features = json_decode($features, true);
  if (!is_array($features)) $features = [];
  $featuresJson = json_encode($features, JSON_UNESCAPED_UNICODE);

  $details = $_POST['details'] ?? [];
  if(is_string($details)) $details = json_decode($details, true);
  if (!is_array($details)) $details = [];
  $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE);

  $pdo->beginTransaction();

  // 處理主檔(文字部分)
  if(!empty($id)) { // 有抓到id
    // UPDATE (編輯模式)
    $sql = "UPDATE products SET  category_id = ?, title = ?,spec = ?, price = ?, tag = ?, keywords = ?,stock_quantity = ?, is_on_shelf = ?, description = ?, features = ?, details = ?, updated_at = NOW() WHERE product_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$category_id, $title, $spec, $price, $tag, $keywords, $stock_quantity, $is_on_shelf, $description, $featuresJson, $detailsJson, $id]);
    $productId = $id;
  } else { // 沒抓到id
    // INSERT (新增模式)
    $sql = "INSERT INTO products(category_id, title, spec, price, tag, keywords, stock_quantity, is_on_shelf, description, features, details, image, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$category_id, $title, $spec, $price, $tag, $keywords, $stock_quantity, $is_on_shelf, $description, $featuresJson, $detailsJson]);
    $productId = $pdo->lastInsertId();
  }

  // 定義上傳目錄
  $uploadDir = __DIR__ . '/../images/shop/';
  // 如果資料夾不存在，自動建立 (權限 0777)
  if(!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

  // 處理商品主圖
  if(!empty($_FILES['main_image']['name'])) {
    $ext = pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION);
    $filename = 'main_' . time() . '_' . rand(100,999) . '.' . $ext;
    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $uploadDir . $filename)) {
      // 存入資料庫的路徑 (相對路徑)
      $dbPath = 'images/shop/' . $filename; 
      
      // 單獨更新 image 欄位
      $sqlImg = "UPDATE products SET image = ? WHERE product_id = ?";
      $pdo->prepare($sqlImg)->execute([$dbPath, $productId]);
    }
  }

  // 處理gallery圖片
  // 邏輯：收集所有要留下的圖片路徑 -> 刪除舊紀錄 -> 寫入新紀錄

  $finalGalleryPaths = [];

  // 舊圖
  if(isset($_POST['existing_gallery']) && is_array($_POST['existing_gallery'])) {
    foreach ($_POST['existing_gallery'] as $url) {
      if(!empty($url)) $finalGalleryPaths[] = $url;
    }
  }

  // 新圖
  if (!empty($_FILES['gallery_files']['name'][0])) {
    $files = $_FILES['gallery_files'];
    $count = count($files['name']);
    
    for ($i = 0; $i < $count; $i++) {
      if ($files['error'][$i] === UPLOAD_ERR_OK) {
        $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $filename = 'gallery_' . time() . '_' . $i . '.' . $ext;
          
        if (move_uploaded_file($files['tmp_name'][$i], $uploadDir . $filename)) {
          // 把新上傳的路徑加入清單
          $finalGalleryPaths[] = 'images/shop/' . $filename;
        }
      }
    }
  }

  // 寫入資料庫
  // 先刪除這個商品的所有相簿紀錄 (Reset)
  $pdo->prepare("DELETE FROM product_gallery WHERE product_id = ?")->execute([$productId]);

  // 如果有圖，依序寫入
  if (!empty($finalGalleryPaths)) {
    $sqlGal = "INSERT INTO product_gallery (product_id, large_url, small_url) VALUES (?, ?, ?)";
    $stmtGal = $pdo->prepare($sqlGal);
    
    foreach ($finalGalleryPaths as $path) {
      // 因為沒有做後端縮圖，large 和 small 存一樣的路徑即可
      $stmtGal->execute([$productId, $path, $path]);
    }
  }


  $pdo->commit();
  echo json_encode([
    'success' => true,
    'message' => '儲存成功'
  ]);

} catch(Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode([
    "error" => $e->getMessage()
  ]);
}
?>