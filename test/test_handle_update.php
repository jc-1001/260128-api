<?php
require_once './team_project/common/connect_cjd102g1.php';

// 確保是 POST 請求才執行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $newWeight = $_POST['weight'] ?? null;

    if ($id && $newWeight !== null) {
        try {
            // 執行 SQL 更新
            $sql = "UPDATE Members SET weight = :weight WHERE member_id = :id";
            $stmt = $pdo->prepare($sql);
            
            // 執行時進行資料清理與類型轉換
            $stmt->execute([
                ':weight' => (float)$newWeight,
                ':id'     => (int)$id
            ]);

            // 更新成功後跳回原本頁面(JS程式)
            echo "<script>alert('資料已更新！'); window.location.href='test_edit_weight.php';</script>";
            exit;
        } catch (PDOException $e) {
            // 錯誤處理：實務上建議記錄在日誌中，避免暴露資料庫資訊
            die("資料庫錯誤：" . $e->getMessage());
        }
    }
}
?>