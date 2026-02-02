<?php
// 1. 允許跨域請求 (讓 Vue 5173 能存取 MAMP 8888)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 增加錯誤回報，方便我們 Debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 引用原本已設定好的連線檔
include 'db_config.php';

// 如果是預檢請求 (OPTIONS)，直接結束程式
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// 2. 引入資料庫連線資訊 (建議將連線邏輯獨立成 db_config.php)

$json = file_get_contents('php://input');
$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    try {
        // 1. 先檢查 Email 是否重複
        $checkStmt = $pdo->prepare("SELECT email FROM members WHERE email = ?");
        $checkStmt->execute([$data['email']]);
        if ($checkStmt->fetch()) {
            echo json_encode(["status" => "error", "message" => "此 Email 已被註冊"]);
            exit;
        }

        // 2. 準備 SQL 語句 (對齊 members (1).sql 欄位名稱)
        $sql = "INSERT INTO members (
            email, password, full_name, phone_number, gender, birth_date, 
            role, account_status, height, weight, blood_type, 
            has_chronic_disease, chronic_disease_description,
            has_family_history, family_history_description,
            has_allergies, allergy_description,
            is_smoking, is_drinking,
            contact_name, relationship, emergency_phone_number,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            'member', 1, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            NOW()
        )";

        $stmt = $pdo->prepare($sql);

        // 3. 執行並處理布林值轉 1/0
        $stmt->execute([
            $data['email'],
            $data['password'],
            $data['full_name'],
            $data['phone_number'] ?? null,
            $data['gender'] ?? 'M',
            $data['birth_date'] ?? null,
            $data['height'] ?? null,
            $data['weight'] ?? null,
            $data['blood_type'] ?? 'A',
            ($data['has_chronic_disease'] ?? false) ? 1 : 0,
            $data['chronic_disease_description'] ?? null,
            ($data['has_family_history'] ?? false) ? 1 : 0,
            $data['family_history_description'] ?? null,
            ($data['has_allergies'] ?? false) ? 1 : 0,
            $data['allergy_description'] ?? null,
            ($data['is_smoking'] ?? false) ? 1 : 0,
            ($data['is_drinking'] ?? false) ? 1 : 0,
            $data['contact_name'] ?? null,
            $data['relationship'] ?? null,
            $data['emergency_phone_number'] ?? null
        ]);

        echo json_encode(["status" => "success", "message" => "註冊成功"]);

    } catch (PDOException $e) {
        // 如果噴錯，會顯示具體的 SQL 錯誤訊息
        echo json_encode(["status" => "error", "message" => "SQL錯誤: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "無效的請求資料"]);
}
?>
