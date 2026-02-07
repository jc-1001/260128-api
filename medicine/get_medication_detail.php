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

$memberId = (int)($_GET['member_id'] ?? ($_POST['member_id'] ?? ($_SESSION['member_id'] ?? 0)));
$medicationId = isset($_GET['medication_id']) ? (int)$_GET['medication_id'] : 0;
if ($medicationId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid medication_id'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $sqlMed = "
    SELECT
      medication_id,
      medication_name,
      note,
      expiry_date,
      stock_qty,
      photo_url,
      is_active
    FROM medications
    WHERE medication_id = :mid
      AND member_id = :member_id
      AND deleted_at IS NULL
    LIMIT 1
  ";
  $stmt = $pdo->prepare($sqlMed);
  $stmt->execute([
    ':mid' => $medicationId,
    ':member_id' => $memberId
  ]);
  $med = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$med) {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $photo = $med['photo_url'] ?: null;

  $sqlSch = "
    SELECT time_slot, instruction, dose_qty
    FROM medication_schedules
    WHERE medication_id = :mid
    ORDER BY schedule_id DESC
  ";
  $stmt = $pdo->prepare($sqlSch);
  $stmt->execute([':mid' => $medicationId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $slots = ['MORNING', 'NOON', 'EVENING', 'BEDTIME'];
  $schedule = [];
  foreach ($slots as $s) {
    $schedule[$s] = [
      'has_schedule' => false,
      'instruction'  => null,
      'dose_qty'     => null,
    ];
  }

  foreach ($rows as $r) {
    $slot = strtoupper((string)$r['time_slot']);
    if (!isset($schedule[$slot])) continue;
    if ($schedule[$slot]['has_schedule']) continue;

    $dose = ($r['dose_qty'] !== null) ? (float)$r['dose_qty'] : null;

    $schedule[$slot] = [
      'has_schedule' => true,
      'instruction'  => $r['instruction'],
      'dose_qty'     => $dose
    ];
  }

  $payload = [
    'data' => [
      'medication_id'   => (int)$med['medication_id'],
      'medication_name' => $med['medication_name'],
      'note'            => $med['note'],
      'expiry_date'     => $med['expiry_date'],
      'stock_qty'       => ($med['stock_qty'] !== null) ? (float)$med['stock_qty'] : null,
      'photo_url'       => $photo,
      'is_active'       => (int)$med['is_active'],
      'schedule'        => $schedule
    ]
  ];

  echo json_encode($payload, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
}