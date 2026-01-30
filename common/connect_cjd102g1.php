<?php
// 判斷是否為本地環境
// 檢查伺服器名稱是否為 localhost 或 127.0.0.1
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {

  // --- 本地端設定 (MAMP) ---
  $db_host = '127.0.0.1';
  $db_port = 8889;
  $db_dbname = 'unicare_db';
  $db_user = 'root';
  $db_password = 'root';
} else {

  // --- 遠端伺服器設定 ---
  $db_host = '127.0.0.1'; // 遠端通常也是填 127.0.0.1 指向它自己的資料庫
  $db_port = 3306;
  $db_dbname = 'tibamefe_cjd102g1';
  $db_user = 'tibamefe_since2021';
  $db_password = 'vwRBSb.j&K#E';
}

$dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_dbname;charset=utf8mb4";

try {
  $pdo = new PDO($dsn, $db_user, $db_password);
  // 開發階段顯示成功訊息，上線後建議註解掉 echo
  // echo '<p style="color: green;">資料庫連線成功。</p>';
  // 設定錯誤模式為 Exception
  // 1. 設定錯誤模式
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // 2. 設定預設回傳格式為關聯陣列 (讓程式碼更簡潔)
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

  // 3. 強制設定時區為台灣 (避免今日資料判定錯誤)
  $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {

  // 4. 判斷如果是 API 請求，回傳 JSON 格式
  header('Content-Type: application/json');
  http_response_code(500);

  // 開發者查看用 (上線後可改為顯示簡短訊息)
  echo json_encode(["err" => "資料庫連線失敗: " . $e->getMessage()]);
  exit();
}
