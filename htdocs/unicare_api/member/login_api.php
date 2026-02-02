<?php
include 'db_config.php'; 

// 設定 Header
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// 接收來自 Vue (Axios) 的 JSON 資料
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "請使用 POST 方法登入"]);
    exit;
}

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "請輸入帳號與密碼"]);
    exit;
}

try {
    // 嚴格對照 ER-Model 表名: Members
    $stmt = $pdo->prepare("SELECT * FROM Members WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 檢查 account_status (1: 正常, 0: 停用)
        if ($user['account_status'] == 1) {
            echo json_encode([
                "status" => "success", // 這裡改為 status 解決 undefined 問題
                "user" => [
                    "email" => $user['email'],
                    "full_name" => $user['full_name'],
                    "account_status" => $user['account_status']
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "該帳號已被停用，請洽管理員"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "電子信箱或密碼錯誤"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "SQL 錯誤: " . $e->getMessage()]);
}
?>