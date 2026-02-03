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
    // 使用 LEFT JOIN 同時抓取會員資料與緊急聯絡人資料
    $sql = "SELECT 
                m.*, 
                ec.contact_name, 
                ec.relationship, 
                ec.phone_number AS emergency_phone_number
            FROM members m
            LEFT JOIN emergency_contacts ec ON m.member_id = ec.member_id
            WHERE m.email = ? AND m.password = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($user['account_status'] == 1) {
            // 這裡回傳所有欄位給前端存入 localStorage
            echo json_encode([
                "status" => "success",
                "user" => $user // 直接回傳整個 $user 陣列，包含身高、體重、聯絡人等
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "該帳號已被停用"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "電子信箱或密碼錯誤"]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "SQL 錯誤: " . $e->getMessage()]);
}
?>