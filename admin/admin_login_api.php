<?php
// admin_login_api.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once '../db_config.php'; // 引入 MAMP 資料庫設定

$data = json_decode(file_get_contents("php://input"), true);

// 根據 ER 模型的帳號 email跟密碼 password
if (isset($data['account']) && isset($data['password'])) {
    try {
        $sql = "SELECT admin_id, admin_name FROM admins WHERE email = :acc AND password = :pwd LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':acc' => $data['account'], ':pwd' => $data['password']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            echo json_encode([
                "success" => true,
                "user" => [
                    "admin_id" => $admin['admin_id'],
                    "full_name" => $admin['admin_name'] // key 傳給前端
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "帳號或密碼錯誤"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "資料庫錯誤"]);
    }
}
?>