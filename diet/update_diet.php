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
    // 接收資料
    $diet_log_id = $_POST['diet_log_id'] ?? null;
    $meal_type   = $_POST['meal_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $meal_time    = !empty($_POST['meal_time']) ? $_POST['meal_time'] : '00:00:00';
    if (!$diet_log_id) {
        throw new Exception("缺少 diet_log_id，無法更新");
    }
    // 先查詢舊資料
    $oldQuery = "SELECT food_image_url FROM diet_logs WHERE diet_log_id = :id";
    $oldStmt = $pdo->prepare($oldQuery);
    $oldStmt->execute(['id' => $diet_log_id]);
    $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);
    if (!$oldData) {
        throw new Exception("找不到該筆紀錄");
    }
    $final_file_name = $oldData['food_image_url']; // 預設使用舊檔名
    // 處理新圖片上傳
    if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] === 0) {
        $upload_dir = '../images/diet/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        // 產生新檔名
        $file_ext = pathinfo($_FILES['food_image']['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $new_file_name;
        if (move_uploaded_file($_FILES['food_image']['tmp_name'], $target_path)) {
            // 上傳成功後，若原本有舊圖，就把舊圖刪掉省空間
            if (!empty($oldData['food_image_url'])) {
                $old_file_path = $upload_dir . $oldData['food_image_url'];
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }
            $final_file_name = $new_file_name; // 更新為新檔名
        }
    }
    // 執行 SQL 更新
    $sql = "UPDATE diet_logs SET 
                meal_time = :meal_time,
                meal_type = :meal_type,
                food_image_url = :food_image_url,
                description = :description,
                updated_at = NOW()
            WHERE diet_log_id = :diet_log_id";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        'meal_time'      => $meal_time,
        'meal_type'      => $meal_type,
        'food_image_url' => $final_file_name,
        'description'    => $description,
        'diet_log_id'    => $diet_log_id
    ]);
    if ($result) {
        $response["success"] = true;
        $response["message"] = "更新成功";
        $response["new_image_url"] = $final_file_name;
    } else {
        $response["message"] = "資料庫更新失敗";
    }
} catch (PDOException $e) {
    $response["success"] = false;
    $response["message"] = "資料庫錯誤：" . $e->getMessage();
} catch (Exception $e) {
    $response["success"] = false;
    $response["message"] = "系統錯誤：" . $e->getMessage();
}
echo json_encode($response);
?>