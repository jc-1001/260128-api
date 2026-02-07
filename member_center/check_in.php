<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

// 會員大改 =============
$data = json_decode(file_get_contents('php://input'), true);
$member_id = isset($data['member_id']) ? intval($data['member_id']) : 0;
// $member_id = 1;

// 簽到會得到的獎勵積分
$reward_points = 50;

try {
  // 檢查今日是否已簽到
  // CURDATE()?
  $sql_check = "SELECT * FROM point_transactions WHERE member_id = ? AND source = 2 AND  DATE(created_at) = CURDATE()";
  $stmt_check = $pdo->prepare($sql_check);
  $stmt_check->execute([$member_id]);

  // rowCount?
  if($stmt_check->rowCount() > 0) {
    echo json_encode([
      'success' => false,
      'message' => '今日已經簽到過了喔!明天再來吧~'
    ]);
    exit;
  } else {
    $sql_insert = "INSERT INTO point_transactions(member_id, source, points_change, created_at) VALUES (?, ?, ?, NOW())";
    $stmt_insert = $pdo->prepare($sql_insert);
    $stmt_insert->execute([$member_id, 2, $reward_points]);
    echo json_encode([
      'success' => true,
      'message' => "簽到成功！獲得 {$reward_points}點",
      "point" => $reward_points
    ]);
  }

} catch(Exception $e) {
  http_response_code(500);
  echo json_encode([
    "error" => $e->getMessage()
  ]);
}
?>