<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

include 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit; }

$data = json_decode(file_get_contents("php://input"), true);

if ($data && isset($data['email'])) {
    try {
        $pdo->beginTransaction();

        // 1. 更新 members 表
        $sql_member = "UPDATE members SET 
            full_name = ?, phone_number = ?, gender = ?, birth_date = ?, 
            blood_type = ?, height = ?, weight = ?
            WHERE email = ?";
        
        $stmt_member = $pdo->prepare($sql_member);
        $stmt_member->execute([
            $data['full_name'],
            $data['phone_number'],
            $data['gender'],
            !empty($data['birth_date']) ? $data['birth_date'] : null,
            $data['blood_type'],
            $data['height'],
            $data['weight'],
            $data['email']
        ]);

        // 2. 更新 emergency_contacts ， 先找 email > member_id
        $stmt_id = $pdo->prepare("SELECT member_id FROM members WHERE email = ?");
        $stmt_id->execute([$data['email']]);
        $member_id = $stmt_id->fetchColumn();

        if ($member_id) {
            $sql_contact = "UPDATE emergency_contacts SET 
                contact_name = ?, relationship = ?, phone_number = ?
                WHERE member_id = ?";
            $stmt_contact = $pdo->prepare($sql_contact);
            $stmt_contact->execute([
                $data['contact_name'],
                $data['relationship'],
                $data['emergency_phone_number'],
                $member_id
            ]);
        }

        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "更新成功"]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => "更新失敗: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "無效的資料"]);
}
?>