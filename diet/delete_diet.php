<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';
// 設定回傳格式為 JSON
header('Content-Type: application/json');
$response = [
    "success" => false,
    "message" => ""
];
try {
    // 接收前端傳來的ID(通常用 POST 或從 URL 取得)
    $diet_log_id = $_POST['diet_log_id'] ?? null;
    if (!$diet_log_id) {
        throw new Exception("缺少必要的 ID 參數");
    }
    // 先查詢該筆資料，取得圖片檔名
    $querySql = "SELECT food_image_url FROM diet_logs WHERE diet_log_id = :id";
    $queryStmt = $pdo->prepare($querySql);
    $queryStmt->execute(['id' => $diet_log_id]);
    $record = $queryStmt->fetch(PDO::FETCH_ASSOC);
    if (!$record) {
        throw new Exception("找不到該筆紀錄，可能已被刪除");
    }
    // 執行資料庫刪除
    $sql = "DELETE FROM diet_logs WHERE diet_log_id = :id";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(['id' => $diet_log_id]);
    if ($result) {
        // 刪除資料庫成功後，嘗試刪除伺服器上的實體圖片檔
        if (!empty($record['food_image_url'])) {
            $upload_dir = '../images/diet/uploads/';
            $file_path = $upload_dir . $record['food_image_url'];
            // 檢查檔案是否存在才執行刪除
            if (file_exists($file_path)) {
                unlink($file_path); 
            }
        }
        $response["success"] = true;
        $response["message"] = "紀錄與圖片已成功刪除";
    } else {
        $response["message"] = "資料庫刪除失敗";
    }
} catch (PDOException $e) {
    $response["success"] = false;
    $response["message"] = "資料庫錯誤：" . $e->getMessage();
} catch (Exception $e) {
    $response["success"] = false;
    $response["message"] = "系統錯誤：" . $e->getMessage();
}
// 輸出結果
echo json_encode($response);
?>