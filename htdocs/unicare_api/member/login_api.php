<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include 'db_config.php'; // 引入您的資料庫連線

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $email = $data['email'];
    $password = $data['password'];

    // 1. 查詢資料庫
    $stmt = $pdo->prepare("SELECT * FROM members WHERE email = ? AND password = ?");
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 2. 檢查帳號狀態是否為「啟用中」 (1)
        if ($user['account_status'] == 1) {
            echo json_encode([
                "success" => true,
                "user" => [
                    "full_name" => $user['full_name'],
                    "role" => $user['role'] // 區分是 member 還是 admin
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "帳號已停用，請聯絡管理員"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "帳號或密碼錯誤"]);
    }
}
?>