<?php
// admin_login_api.php
// 1. 強制開啟顯示錯誤 (除錯用，成功後可刪除)
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // 2. 引入檔案
    require_once __DIR__ . '/../common/cors.php';
    require_once __DIR__ . '/../common/connect_cjd102g1.php';

    // 3. 設定 Header (注意：這必須在任何 echo 之前)
    header("Content-Type: application/json; charset=UTF-8");

    // 4. 接收資料
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['account']) && isset($data['password'])) {
        $sql = "SELECT admin_id, admin_name FROM admins WHERE email = :acc AND password = :pwd LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':acc' => $data['account'], ':pwd' => $data['password']]);
        $admin = $stmt->fetch();

        if ($admin) {
            echo json_encode([
                "success" => true,
                "user" => [
                    "admin_id" => $admin['admin_id'],
                    "full_name" => $admin['admin_name']
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "帳號或密碼錯誤"]);
        }
    } else {
        // 如果沒有接收到資料，也回傳一個 JSON
        echo json_encode(["success" => false, "message" => "未接收到登入資料"]);
    }

} catch (Throwable $e) {
    // 捕捉所有錯誤 (包含路徑錯誤、語法錯誤、DB錯誤)
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false,
        "message" => "系統錯誤: " . $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
}
?>