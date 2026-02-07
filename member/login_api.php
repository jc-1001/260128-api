<?php
declare(strict_types=1);

ob_start();
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
http_response_code(204);
exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
echo json_encode(["status" => "error", "message" => "請使用 POST 方法登入"], JSON_UNESCAPED_UNICODE);
exit;
}

// 接收 Vue (Axios) 的 JSON
$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
echo json_encode(["status" => "error", "message" => "請輸入帳號密碼"], JSON_UNESCAPED_UNICODE);
exit;
}

try {
$sql = "
SELECT
m.member_id,
m.email,
m.password,
m.full_name,
m.phone_number,
m.gender,
m.birth_date,
m.role,
m.account_status,
m.created_at,
m.updated_at,
m.last_login_at,
m.height,
m.weight,
m.blood_type,
m.has_chronic_disease,
m.chronic_disease_description,
m.has_family_history,
m.family_history_description,
m.has_allergies,
m.allergy_description,
m.is_smoking,
m.is_drinking,
m.current_points,
m.points_updated_at
FROM members m
WHERE m.email = ?
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
echo json_encode(["status" => "error", "message" => "帳號或密碼錯誤"], JSON_UNESCAPED_UNICODE);
exit;
}

if ((int)$user['account_status'] !== 1) {
echo json_encode(["status" => "error", "message" => "該帳號已被停用"], JSON_UNESCAPED_UNICODE);
exit;
}

// 明碼比對（暫時）
if ($user['password'] !== $password) {
echo json_encode(["status" => "error", "message" => "帳號或密碼錯誤"], JSON_UNESCAPED_UNICODE);
exit;
}

unset($user['password']); // 不回傳密碼

echo json_encode([
"status" => "success",
"user" => $user
], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
echo json_encode(["status" => "error", "message" => "SQL 錯誤: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}