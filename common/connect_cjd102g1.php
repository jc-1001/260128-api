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
    echo '<p style="color: green;">資料庫連線成功。</p>';
    // 設定錯誤模式為 Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  } catch (PDOException $e) {

    // 遠端伺服器建議不要 echo 詳細錯誤訊息以免洩漏結構，可以改寫入 Log
    echo '<p style="color: red;">資料庫連線失敗。</p>';
    exit();
  }
?>