<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

$member_id = 1;

try {
  $sql = "SELECT IFNULL(SUM(points_change), 0) as total FROM point_transactions WHERE member_id = ?";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$member_id]);

  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  echo json_encode([
    'success' => true,
    'total_points' => intval($result['total'])
  ]);
} catch(Exception $e) {
  http_response_code(500);
  echo json_encode([
    "error" => $e->getMessage()
  ]);
}
?>