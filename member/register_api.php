<?php
// 1. 允許跨域請求 (本地存資料庫)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 報錯
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 引用連線檔
include 'db_config.php';

// 如果是預檢請求 (OPTIONS)，請直接結束程式
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    try {
        // 1. 檢查帳號是否重複
        $checkStmt = $pdo->prepare("SELECT email FROM members WHERE email = ?");
        $checkStmt->execute([$data['email']]);
        if ($checkStmt->fetch()) {
            echo json_encode(["status" => "error", "message" => "此 Email 已被註冊"]);
            exit;
        }

        // 2. SQL 指令
        $sql_member = "INSERT INTO members (
            email, password, full_name, phone_number, gender, birth_date, 
            role, account_status, height, weight, blood_type, 
            has_chronic_disease, chronic_disease_description,
            has_family_history, family_history_description,
            has_allergies, allergy_description,
            is_smoking, is_drinking,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            'member', 1, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, 
            NOW()
        )";

        $sql_contact = "INSERT INTO emergency_contacts (
            member_id, contact_name, relationship, phone_number
        ) VALUES (?, ?, ?, ?)";

        // 開啟事務處理
        $pdo->beginTransaction();

        // 寫入會員
        $stmt_member = $pdo->prepare($sql_member);
        $stmt_member->execute([
            $data['email'],
            $data['password'],
            $data['full_name'],
            $data['phone_number'] ?? null,
            $data['gender'] ?? 'M',
            (!empty($data['birth_date'])) ? $data['birth_date'] : null,
            // ----------------------------------------------
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
            ($data['is_drinking'] ?? false) ? 1 : 0
        ]);

        // 取得會員編號
        $new_member_id = $pdo->lastInsertId();

        // 緊急聯絡人寫入
        $stmt_contact = $pdo->prepare($sql_contact);
        $stmt_contact->execute([
            $new_member_id,
            $data['contact_name'] ?? '',
            $data['relationship'] ?? '',
            $data['emergency_phone_number'] ?? ''
        ]);

        // 提交
        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "註冊成功"]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(["status" => "error", "message" => "註冊失敗: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "無效的請求"]);
}

?>