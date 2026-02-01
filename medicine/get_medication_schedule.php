<?php
declare(strict_types=1);

ob_start();

$allowedOrigin = 'http://localhost:5173';
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

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
      ms.schedule_id,
      m.medication_id,
      m.medication_name,
      m.category,
      m.photo_url,
      ms.time_slot,
      ms.instruction,
      ms.dose_qty,
      CASE WHEN mr.schedule_id IS NULL THEN 0 ELSE 1 END AS taken_today
    FROM medication_schedules ms
    INNER JOIN medications m ON m.medication_id = ms.medication_id
    LEFT JOIN medication_records mr
      ON mr.schedule_id = ms.schedule_id
     AND mr.intake_date = CURDATE()
    WHERE m.member_id = :member_id
      AND m.deleted_at IS NULL
      AND m.is_active = 1
      AND ms.instruction IS NOT NULL
      AND ms.instruction <> 'NONE'
    ORDER BY ms.time_slot ASC, m.medication_name ASC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':member_id' => $memberId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $payload = array_map(function ($r) {
    return [
      'schedule_id' => (int)$r['schedule_id'],
      'medication_id' => (int)$r['medication_id'],
      'medication_name' => $r['medication_name'],
      'category' => $r['category'],
      'photo_url' => $r['photo_url'],
      'time_slot' => $r['time_slot'],
      'instruction' => $r['instruction'],
      'dose_qty' => ($r['dose_qty'] === null ? null : (float)$r['dose_qty']),
      'taken_today' => (int)$r['taken_today'],
    ];
  }, $rows);

  ob_clean();
  echo json_encode(['data' => $payload], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  ob_clean();
  echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
}