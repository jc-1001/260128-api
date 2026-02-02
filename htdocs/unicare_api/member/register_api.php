<?php
// 1. 允許跨域請求 (讓 Vue 5173 能存取 MAMP 8888)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// 如果是預檢請求 (OPTIONS)，直接結束程式
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

// 2. 引入資料庫連線資訊 (建議將連線邏輯獨立成 db_config.php)
$host = '127.0.0.1';
$db   = 'unicare_db';
$user = 'root';
$pass = 'root'; // MAMP 預設密碼
$port = 8889;   // MAMP 預設 Port

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "資料庫連線失敗"]);
    exit;
}

// 3. 接收並解析前端傳來的 JSON 資料
$json = file_get_contents('php://input');
$data = json_decode(file_get_contents("php://input"), true);

if ($data) {
    try {
        // 4. 準備 SQL 指令 (使用預處理語句防止 SQL 注入)
        $sql = "INSERT INTO members (full_name, email, password, created_at, account_status) 
        VALUES (:full_name, :email, :password, NOW(), 1)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':full_name' => $data['full_name'],
            ':email'     => $data['email'],
            ':password'  => $data['password']
        ]);

        echo json_encode(["success" => true, "message" => "註冊成功"]);
    } catch (PDOException $e) {
        // 檢查 email 是否重複
        if ($e->getCode() == 23000) {
            echo json_encode(["success" => false, "message" => "此 Email 已被註冊"]);
        } else {
            echo json_encode(["success" => false, "message" => "註冊失敗：" . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(["success" => false, "message" => "無效的請求資料"]);
}
?>
