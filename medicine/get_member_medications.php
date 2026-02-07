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
$category = (string)($_GET['category'] ?? '藥品');

if ($memberId <= 0) {
  http_response_code(400);
  ob_clean();
  echo json_encode(['error' => 'Invalid member_id'], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $sql = "
    SELECT
      m.medication_id,
      m.medication_name,
      m.photo_url,
      m.stock_qty,
      m.expiry_date,
      m.category,
      m.note,
      m.is_active,
      ms.schedule_id,
      ms.time_slot,
      ms.instruction,
      ms.dose_qty
    FROM medications m
    LEFT JOIN medication_schedules ms
      ON ms.medication_id = m.medication_id
    WHERE m.member_id = :member_id
      AND m.category = :category
      AND m.deleted_at IS NULL
    ORDER BY m.medication_id ASC, ms.schedule_id DESC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':member_id' => $memberId,
    ':category' => $category,
  ]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $items = [];
  foreach ($rows as $r) {
    $id = (int)$r['medication_id'];

    if (!isset($items[$id])) {
      $items[$id] = [
        'medication_id' => $id,
        'medication_name' => $r['medication_name'],
        'photo_url' => $r['photo_url'],
        'stock_qty' => ($r['stock_qty'] === null ? null : (float)$r['stock_qty']),
        'expiry_date' => ($r['expiry_date'] === null || $r['expiry_date'] === '' ? null : $r['expiry_date']),
        'category' => $r['category'],
        'note' => $r['note'],
        'is_active' => ($r['is_active'] === null ? null : (int)$r['is_active']),
        'schedule' => [
          'MORNING' => ['instruction' => 'NONE', 'dose_qty' => null],
          'NOON' => ['instruction' => 'NONE', 'dose_qty' => null],
          'EVENING' => ['instruction' => 'NONE', 'dose_qty' => null],
          'BEDTIME' => ['instruction' => 'NONE', 'dose_qty' => null],
        ],
        '_filled' => [],
      ];
    }

    $slot = $r['time_slot'] ? strtoupper((string)$r['time_slot']) : null;
    if ($slot && isset($items[$id]['schedule'][$slot]) && !isset($items[$id]['_filled'][$slot])) {
      $instruction = $r['instruction'] ?? 'NONE';
      $dose = ($r['dose_qty'] === null ? null : (float)$r['dose_qty']);
      $items[$id]['schedule'][$slot] = [
        'instruction' => $instruction,
        'dose_qty' => $dose,
      ];
      $items[$id]['_filled'][$slot] = true;
    }
  }

  $payload = array_values(array_map(function ($item) {
    unset($item['_filled']);
    return $item;
  }, $items));

  ob_clean();
  echo json_encode(['data' => $payload], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  ob_clean();
  echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
}