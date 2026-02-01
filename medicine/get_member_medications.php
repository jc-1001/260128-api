<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/cors.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

require_once __DIR__ . '/../common/connect_cjd102g1.php';

session_start();
$memberId = (int)($_SESSION['member_id'] ?? ($_GET['member_id'] ?? 1));
$category = $_GET['category'] ?? '?鈭?';

if ($memberId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid member_id'], JSON_UNESCAPED_UNICODE);
  exit;
}

$sql = "
  SELECT
    m.medication_id,
    m.medication_name,
    m.photo_url,
    m.stock_qty,
    m.expiry_date,
    m.category
  FROM medications m
  WHERE m.member_id = :member_id
    AND m.category = :category
    AND m.deleted_at IS NULL
  ORDER BY m.medication_id ASC
";

try {
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
  ':member_id' => $memberId,
  ':category' => $category
]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $payload = array_map(function ($r) {
    return [
      'medication_id'   => (int)$r['medication_id'],
      'medication_name' => $r['medication_name'],
      'photo_url'       => $r['photo_url'],
      'stock_qty'       => ($r['stock_qty'] === null ? null : (float)$r['stock_qty']),
      'expiry_date'     => ($r['expiry_date'] === null || $r['expiry_date'] === '' ? '未填寫' : $r['expiry_date']),
    ];
  }, $rows);

  echo json_encode(['data' => $payload], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
}