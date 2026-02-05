<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/../common/cors.php';

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

$medicationName = trim((string)($_POST['medication_name'] ?? ''));
$note           = trim((string)($_POST['note'] ?? ''));
$expiryDate     = trim((string)($_POST['expiry_date'] ?? ''));
$stockQtyRaw    = trim((string)($_POST['stock_qty'] ?? ''));
$category       = trim((string)($_POST['category'] ?? ''));

$scheduleInput = [
  'MORNING'  => ['instruction' => (string)($_POST['morning_instruction']  ?? ''), 'dose_qty' => (string)($_POST['morning_dose_qty']  ?? '')],
  'NOON'     => ['instruction' => (string)($_POST['noon_instruction']     ?? ''), 'dose_qty' => (string)($_POST['noon_dose_qty']     ?? '')],
  'EVENING'  => ['instruction' => (string)($_POST['evening_instruction']  ?? ''), 'dose_qty' => (string)($_POST['evening_dose_qty']  ?? '')],
  'BEDTIME'  => ['instruction' => (string)($_POST['bedtime_instruction']  ?? ''), 'dose_qty' => (string)($_POST['bedtime_dose_qty']  ?? '')],
];

if ($medicationName === '' || mb_strlen($medicationName) > 200) {
  http_response_code(400);
  ob_clean();
  echo json_encode(['error' => 'Invalid medication_name'], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($category === '' || mb_strlen($category) > 20) {
  http_response_code(400);
  ob_clean();
  echo json_encode(['error' => 'Invalid category'], JSON_UNESCAPED_UNICODE);
  exit;
}

$expiryDateVal = null;
if ($expiryDate !== '') {
  $dt = DateTime::createFromFormat('Y-m-d', $expiryDate);
  if (!$dt || $dt->format('Y-m-d') !== $expiryDate) {
    http_response_code(400);
    ob_clean();
    echo json_encode(['error' => 'Invalid expiry_date'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $expiryDateVal = $expiryDate;
}

$stockQtyVal = null;
if ($stockQtyRaw !== '') {
  if (!is_numeric($stockQtyRaw)) {
    http_response_code(400);
    ob_clean();
    echo json_encode(['error' => 'Invalid stock_qty'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $stockQtyVal = (float)$stockQtyRaw;
  if ($stockQtyVal < 0) {
    http_response_code(400);
    ob_clean();
    echo json_encode(['error' => 'Invalid stock_qty'], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

$photoUrl = null;
$savedAbsPath = null;

if (isset($_FILES['photo']) && is_array($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
  if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    ob_clean();
    echo json_encode(['error' => 'Photo upload failed'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $mime = null;
  if (class_exists('finfo')) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['photo']['tmp_name']);
  } elseif (function_exists('mime_content_type')) {
    $mime = mime_content_type($_FILES['photo']['tmp_name']);
  } elseif (function_exists('exif_imagetype')) {
    $imgType = exif_imagetype($_FILES['photo']['tmp_name']);
    $map = [
      IMAGETYPE_JPEG => 'image/jpeg',
      IMAGETYPE_PNG  => 'image/png',
      IMAGETYPE_WEBP => 'image/webp',
    ];
    $mime = $map[$imgType] ?? null;
  } else {
    $imgInfo = getimagesize($_FILES['photo']['tmp_name']);
    $mime = $imgInfo['mime'] ?? null;
  }

  $allow = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];
  if (!$mime || !isset($allow[$mime])) {
    http_response_code(400);
    ob_clean();
    echo json_encode(['error' => 'Invalid photo type'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $relDir = "/images/medications/{$memberId}";
  $absDir = dirname(__DIR__) . $relDir;

  if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['error' => 'Cannot create upload dir'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $filename = bin2hex(random_bytes(16)) . '.' . $allow[$mime];
  $savedAbsPath = $absDir . DIRECTORY_SEPARATOR . $filename;

  if (!move_uploaded_file($_FILES['photo']['tmp_name'], $savedAbsPath)) {
    http_response_code(500);
    ob_clean();
    echo json_encode(['error' => 'Cannot save photo'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $photoUrl = $relDir . '/' . $filename;
}

try {
  $pdo->beginTransaction();

  $sqlMed = "
    INSERT INTO medications
      (member_id, product_id, order_item_id, category, source_type, medication_name, photo_url, expiry_date, stock_qty, note, is_active, deleted_at)
    VALUES
      (:member_id, NULL, NULL, :category, 'MANUAL', :medication_name, :photo_url, :expiry_date, :stock_qty, :note, 1, NULL)
  ";

  $stmt = $pdo->prepare($sqlMed);
  $stmt->execute([
    ':member_id'       => $memberId,
    ':category'        => $category,
    ':medication_name' => $medicationName,
    ':photo_url'       => $photoUrl,
    ':expiry_date'     => $expiryDateVal,
    ':stock_qty'       => $stockQtyVal,
    ':note'            => ($note === '' ? null : $note),
  ]);

  $medicationId = (int)$pdo->lastInsertId();

  $sqlSch = "
    INSERT INTO medication_schedules
      (medication_id, time_slot, instruction, dose_qty)
    VALUES
      (:medication_id, :time_slot, :instruction, :dose_qty)
  ";
  $stmtSch = $pdo->prepare($sqlSch);

  $created = 0;

  foreach ($scheduleInput as $timeSlot => $v) {
    $instruction = strtoupper(trim((string)$v['instruction']));
    $doseRaw     = trim((string)$v['dose_qty']);

    if ($instruction === '' || $instruction === 'NONE') {
      continue;
    }

    if ($timeSlot === 'BEDTIME') {
      if ($instruction !== 'BEDTIME') {
        http_response_code(400);
        $pdo->rollBack();
        if ($savedAbsPath && is_file($savedAbsPath)) @unlink($savedAbsPath);
        ob_clean();
        echo json_encode(['error' => 'Invalid instruction: BEDTIME'], JSON_UNESCAPED_UNICODE);
        exit;
      }
    } else {
      if (!in_array($instruction, ['BEFORE_MEAL','AFTER_MEAL','ANY'], true)) {
        http_response_code(400);
        $pdo->rollBack();
        if ($savedAbsPath && is_file($savedAbsPath)) @unlink($savedAbsPath);
        ob_clean();
        echo json_encode(['error' => "Invalid instruction: {$timeSlot}"], JSON_UNESCAPED_UNICODE);
        exit;
      }
    }

    if ($doseRaw === '' || !is_numeric($doseRaw)) {
      http_response_code(400);
      $pdo->rollBack();
      if ($savedAbsPath && is_file($savedAbsPath)) @unlink($savedAbsPath);
      ob_clean();
      echo json_encode(['error' => "Invalid dose_qty: {$timeSlot}"], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $doseQty = (float)$doseRaw;
    if ($doseQty <= 0) {
      http_response_code(400);
      $pdo->rollBack();
      if ($savedAbsPath && is_file($savedAbsPath)) @unlink($savedAbsPath);
      ob_clean();
      echo json_encode(['error' => "Invalid dose_qty: {$timeSlot}"], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $stmtSch->execute([
      ':medication_id' => $medicationId,
      ':time_slot'     => $timeSlot,
      ':instruction'   => $instruction,
      ':dose_qty'      => $doseQty,
    ]);
    $created++;
  }

  $pdo->commit();

  ob_clean();
  echo json_encode([
    'data' => [
      'medication_id' => $medicationId,
      'medication_name' => $medicationName,
      'photo_url' => $photoUrl,
      'schedules_created' => $created
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  if ($savedAbsPath && is_file($savedAbsPath)) @unlink($savedAbsPath);

  http_response_code(500);
  ob_clean();
  echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
}
