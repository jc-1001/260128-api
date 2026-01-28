<?php
require_once __DIR__ . '/../common/connect_cjd102g1.php';

try {
    // 使用預處理語句，根據姓名查詢
    $name = "王小明";
    $stmt = $pdo->prepare("SELECT * FROM Members WHERE full_name = :name");
    $stmt->execute(['name' => $name]);
    $user = $stmt->fetch();

    if ($user) {
        echo "<h2>會員詳細資料</h2>";
        echo "姓名：" . htmlspecialchars($user['full_name']) . "<br>";
        echo "性別：" . ($user['gender'] == 'M' ? '男' : '女') . "<br>";
        echo "身高：" . $user['height'] . " cm<br>";
        echo "體重：" . $user['weight'] . " kg<br>";
        echo "目前的積分：" . $user['current_points'] . " 分<br>";
        echo "慢性病史：" . ($user['has_chronic_disease'] ? $user['chronic_disease_description'] : '無') . "<br>";
        
    } else {
        echo "找不到該會員。";
    }

} catch (Exception $e) {
    echo "執行錯誤：" . $e->getMessage();
}
?>