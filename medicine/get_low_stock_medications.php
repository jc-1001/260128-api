<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../common/cors.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  ob_clean();
  echo json_encode(['error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

require_once __DIR__ . '/../common/connect_cjd102g1.php';
session_start();

$memberId = (int)($_SESSION['member_id'] ?? 1);
if ($memberId <= 0) {
  http_response_code(401);
  ob_clean();
  echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $sql = "
    SELECT
      m.medication_id,
      m.medication_name,
      m.category,
      m.stock_qty,
      COALESCE(SUM(CASE
        WHEN ms.instruction IS NULL OR ms.instruction = 'NONE' THEN 0
        ELSE ms.dose_qty
      END), 0) AS daily_dose
    FROM medications m
    LEFT JOIN medication_schedules ms
      ON ms.medication_id = m.medication_id
    WHERE m.member_id = :member_id
      AND m.deleted_at IS NULL
      AND m.is_active = 1
    GROUP BY m.medication_id, m.medication_name, m.category, m.stock_qty
    ORDER BY m.medication_id ASC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':member_id' => $memberId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $result = [];
  foreach ($rows as $r) {
    $stockQty = $r['stock_qty'] === null ? null : (float)$r['stock_qty'];
    $dailyDose = (float)$r['daily_dose'];

    if ($stockQty === null) {
      continue;
    }

    if ($stockQty <= 0) {
      $result[] = [
        'medication_name' => $r['medication_name'],
        'category' => $r['category'],
        'days_left' => 0,
        'message' => '已無庫存'
      ];
      continue;
    }

    if ($dailyDose <= 0) {
      continue;
    }

    $daysLeft = (int)floor($stockQty / $dailyDose);
    if ($daysLeft <= 7) {
      $result[] = [
        'medication_name' => $r['medication_name'],
        'category' => $r['category'],
        'days_left' => $daysLeft,
        'message' => "剩{$daysLeft}天份"
      ];
    }
  }

  ob_clean();
  echo json_encode(['data' => $result], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  ob_clean();
  echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
}
