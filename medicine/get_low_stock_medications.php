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
      m.expiry_date,
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
    GROUP BY m.medication_id, m.medication_name, m.category, m.stock_qty, m.expiry_date
    ORDER BY m.medication_id ASC
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':member_id' => $memberId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $today = new DateTime('today');
  $result = [];

  foreach ($rows as $r) {
    $messages = [];

    $stockQty = $r['stock_qty'] === null ? null : (float)$r['stock_qty'];
    $dailyDose = (float)$r['daily_dose'];

    if ($stockQty !== null) {
      if ($stockQty <= 0) {
        $messages[] = '已無庫存';
      } elseif ($dailyDose > 0) {
        $daysLeft = (int)floor($stockQty / $dailyDose);
        if ($daysLeft <= 7) {
          $messages[] = "剩{$daysLeft}天份";
        }
      }
    }

    $expiryRaw = $r['expiry_date'] ?? null;
    if ($expiryRaw) {
      $expiryDate = DateTime::createFromFormat('Y-m-d', $expiryRaw);
      if ($expiryDate) {
        $diffDays = (int)$today->diff($expiryDate)->format('%r%a');
        if ($diffDays <= 0) {
          $messages[] = '已過期';
        } elseif ($diffDays <= 30) {
          $messages[] = "有效期限剩{$diffDays}天";
        }
      }
    }

    if (empty($messages)) {
      continue;
    }

    $result[] = [
      'medication_name' => $r['medication_name'],
      'category' => $r['category'],
      'message' => implode(' / ', $messages),
    ];
  }

  ob_clean();
  echo json_encode(['data' => $result], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  ob_clean();
  echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
}