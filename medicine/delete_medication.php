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

$memberId = (int)($_SESSION['member_id'] ?? 0);

if ($memberId <= 0) {
  $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
  $memberId = (int)($_POST['member_id'] ?? 0);

  if (!$isLocal || $memberId <= 0) {
    http_response_code(401);
    ob_clean();
    echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

$medicationId = (int)($_POST['medication_id'] ?? 0);
if ($medicationId <= 0) {
  http_response_code(400);
  ob_clean();
  echo json_encode(['error' => 'Invalid medication_id'], JSON_UNESCAPED_UNICODE);
  exit;
}

$photoToDeleteAbs = null;

try {
  $pdo->beginTransaction();

  $stmt = $pdo->prepare("
    SELECT photo_url
    FROM medications
    WHERE medication_id = :mid AND member_id = :member_id
    LIMIT 1
  ");
  $stmt->execute([':mid' => $medicationId, ':member_id' => $memberId]);
  $med = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$med) {
    http_response_code(404);
    $pdo->rollBack();
    ob_clean();
    echo json_encode(['error' => 'Not Found'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $photo = (string)($med['photo_url'] ?? '');
  if ($photo !== '') {
    if (str_starts_with($photo, '/images/')) {
      $photoToDeleteAbs = dirname(__DIR__) . $photo;
    } elseif (str_starts_with($photo, 'images/')) {
      $photoToDeleteAbs = dirname(__DIR__) . '/' . $photo;
    }
  }

  $stmt = $pdo->prepare("
    DELETE mr
    FROM medication_records mr
    INNER JOIN medication_schedules ms ON ms.schedule_id = mr.schedule_id
    WHERE ms.medication_id = :mid
  ");
  $stmt->execute([':mid' => $medicationId]);
  $deletedRecords = $stmt->rowCount();

  $stmt = $pdo->prepare("DELETE FROM medication_schedules WHERE medication_id = :mid");
  $stmt->execute([':mid' => $medicationId]);
  $deletedSchedules = $stmt->rowCount();

  $stmt = $pdo->prepare("
    DELETE FROM medications
    WHERE medication_id = :mid AND member_id = :member_id
    LIMIT 1
  ");
  $stmt->execute([':mid' => $medicationId, ':member_id' => $memberId]);
  $deletedMedications = $stmt->rowCount();

  $pdo->commit();

  if ($photoToDeleteAbs && is_file($photoToDeleteAbs)) {
    @unlink($photoToDeleteAbs);
  }

  ob_clean();
  echo json_encode([
    'data' => [
      'medication_id' => $medicationId,
      'deleted' => true,
      'deleted_counts' => [
        'medications' => $deletedMedications,
        'schedules'   => $deletedSchedules,
        'records'     => $deletedRecords
      ]
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();

  http_response_code(500);
  ob_clean();
  echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
}