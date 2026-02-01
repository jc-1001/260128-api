<?php
declare(strict_types=1);

ob_start();

$allowedOrigin = 'http://localhost:5173';
header("Access-Control-Allow-Origin: {$allowedOrigin}");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$scheduleIds = $_POST['schedule_ids'] ?? [];
if (!is_array($scheduleIds) || empty($scheduleIds)) {
  http_response_code(400);
  ob_clean();
  echo json_encode(['error' => 'Invalid schedule_ids'], JSON_UNESCAPED_UNICODE);
  exit;
}

$scheduleIds = array_values(array_filter(array_map('intval', $scheduleIds), fn($v) => $v > 0));
if (empty($scheduleIds)) {
  http_response_code(400);
  ob_clean();
  echo json_encode(['error' => 'Invalid schedule_ids'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo->beginTransaction();

  $placeholders = implode(',', array_fill(0, count($scheduleIds), '?'));
  $sql = "
    SELECT ms.schedule_id, ms.dose_qty
    FROM medication_schedules ms
    INNER JOIN medications m ON m.medication_id = ms.medication_id
    WHERE ms.schedule_id IN ({$placeholders})
      AND m.member_id = ?
      AND m.is_active = 1
      AND m.deleted_at IS NULL
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([...$scheduleIds, $memberId]);
  $validRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($validRows)) {
    $pdo->rollBack();
    http_response_code(400);
    ob_clean();
    echo json_encode(['error' => 'No valid schedules'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $today = date('Y-m-d');
  $now = date('Y-m-d H:i:s');

  $stmtIns = $pdo->prepare("
    INSERT INTO medication_records (schedule_id, intake_date, taken_at, consumed_qty)
    VALUES (:schedule_id, :intake_date, :taken_at, :consumed_qty)
  ");

  $created = 0;
  foreach ($validRows as $row) {
    $doseQty = $row['dose_qty'] === null ? null : (float)$row['dose_qty'];
    $stmtIns->execute([
      ':schedule_id' => (int)$row['schedule_id'],
      ':intake_date' => $today,
      ':taken_at' => $now,
      ':consumed_qty' => $doseQty,
    ]);
    $created++;
  }

  $pdo->commit();

  ob_clean();
  echo json_encode([
    'data' => [
      'created' => $created,
      'intake_date' => $today,
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  ob_clean();
  $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  if ($isLocal) {
    echo json_encode([
      'error' => 'Server Error',
      'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
  } else {
    echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
  }
}
