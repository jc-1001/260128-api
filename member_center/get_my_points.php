<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

// $member_id = 1;
// 會員大改
$member_id = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;

try {
  // 找出最新20筆資料
  $sql = "SELECT * FROM point_transactions WHERE member_id = ? ORDER BY created_at DESC LIMIT 20";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$member_id]);

  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // 資料加工 (Mapping)
  $history = [];
  foreach ($rows as $row) {
    // 把 source 數字轉成中文標題
    $title = '未知來源';

    if($row['source'] == 1) $title = '會員註冊禮';
    if($row['source'] == 2) $title = '每日簽到獎勵';
    if($row['source'] == 3) $title = '購物折抵';

    $history[] = [
      'id' => $row['point_log_id'],
      'title' => $title,
      // 切掉秒數
      'date' => substr($row['created_at'], 0, 16),
      'amount' => $row['points_change'],
      'source' => $row['source'] // 留著判斷來源用
    ];
  }

  echo json_encode([
    'success' => true,
    'data' => $history
  ]);


} catch(Exception $e) {
  http_response_code(500);
  echo json_encode([
    "error" => $e->getMessage()
  ]);
}