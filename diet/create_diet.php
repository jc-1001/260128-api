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
    // 接收前端傳來的資料
    $member_id   = $_POST['member_id'] ?? 1;
    $meal_type   = $_POST['meal_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $meal_date   = $_POST['meal_date'] ?? date('Y-m-d');
    // 確保時間格式正確
    $meal_time   = !empty($_POST['meal_time']) ? $_POST['meal_time'] : '00:00:00';
    $final_file_name = "";
    // 處理圖片上傳
    if (isset($_FILES['food_image']) && $_FILES['food_image']['error'] === 0) {
        $upload_dir = '../images/diet/uploads/'; 
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES['food_image']['name'], PATHINFO_EXTENSION);
        $new_file_name = uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $new_file_name;
        if (move_uploaded_file($_FILES['food_image']['tmp_name'], $target_path)) {
            $final_file_name = $new_file_name; 
        }
    }
    $sql = "INSERT INTO diet_logs (
            member_id, 
            meal_date, 
            meal_time, 
            meal_type, 
            food_image_url, 
            description, 
            created_at, 
            updated_at
        ) VALUES (
            :member_id, 
            :meal_date, 
            :meal_time, 
            :meal_type, 
            :food_image_url, 
            :description, 
            NOW(), 
            NOW()
        )";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    'member_id'      => $member_id,
    'meal_date'      => $meal_date,
    'meal_time'      => $meal_time,
    'meal_type'      => $meal_type,
    'food_image_url' => $final_file_name,
    'description'    => $description
]);
    if ($result) {
        $response["success"] = true;
        $response["message"] = "紀錄新增成功";
    } else {
        $response["message"] = "資料庫寫入失敗";
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