<?php
declare(strict_types=1);
require_once __DIR__ . '/../common/cors.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  ob_clean();
  echo json_encode(['error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

require_once __DIR__ . '/../common/connect_cjd102g1.php'; // ???????$pdo?雓?????echo/print
session_start();

$memberId = (int)($_SESSION['member_id'] ?? 1);

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

$has = fn(string $k) => array_key_exists($k, $_POST);

try {
  $stmt = $pdo->prepare("
    SELECT medication_id, photo_url
    FROM medications
    WHERE medication_id = :mid
      AND member_id = :member_id
      AND deleted_at IS NULL
    LIMIT 1
  ");
  $stmt->execute([':mid' => $medicationId, ':member_id' => $memberId]);
  $current = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$current) {
    http_response_code(404);
    ob_clean();
    echo json_encode(['error' => 'Not Found'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $pdo->beginTransaction();

  
  $set = [];
  $params = [':mid' => $medicationId, ':member_id' => $memberId];

  if ($has('medication_name')) {
    $name = trim((string)$_POST['medication_name']);
    if ($name === '' || mb_strlen($name) > 200) {
      http_response_code(400);
      $pdo->rollBack();
      ob_clean();
      echo json_encode(['error' => 'Invalid medication_name'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $set[] = "medication_name = :medication_name";
    $params[':medication_name'] = $name;
  }

  if ($has('note')) {
    $note = trim((string)$_POST['note']);
    $set[] = "note = :note";
    $params[':note'] = ($note === '') ? null : $note;
  }

  if ($has('expiry_date')) {
    $expiry = trim((string)$_POST['expiry_date']);
    if ($expiry === '') {
      $set[] = "expiry_date = NULL";
    } else {
      $dt = DateTime::createFromFormat('Y-m-d', $expiry);
      if (!$dt || $dt->format('Y-m-d') !== $expiry) {
        http_response_code(400);
        $pdo->rollBack();
        ob_clean();
        echo json_encode(['error' => 'Invalid expiry_date'], JSON_UNESCAPED_UNICODE);
        exit;
      }
      $set[] = "expiry_date = :expiry_date";
      $params[':expiry_date'] = $expiry;
    }
  }

  if ($has('stock_qty')) {
    $stockRaw = trim((string)$_POST['stock_qty']);
    if ($stockRaw === '') {
      $set[] = "stock_qty = NULL";
    } else {
      if (!is_numeric($stockRaw)) {
        http_response_code(400);
        $pdo->rollBack();
        ob_clean();
        echo json_encode(['error' => 'Invalid stock_qty'], JSON_UNESCAPED_UNICODE);
        exit;
      }
      $stock = (float)$stockRaw;
      if ($stock < 0) {
        http_response_code(400);
        $pdo->rollBack();
        ob_clean();
        echo json_encode(['error' => 'Invalid stock_qty'], JSON_UNESCAPED_UNICODE);
        exit;
      }
      $set[] = "stock_qty = :stock_qty";
      $params[':stock_qty'] = $stock;
    }
  }

  if ($has('category')) {
    $cat = trim((string)$_POST['category']);
    $set[] = "category = :category";
    $params[':category'] = ($cat === '') ? null : $cat;
  }

  if ($has('is_active')) {
    $ia = (int)$_POST['is_active'];
    if (!in_array($ia, [0, 1], true)) {
      http_response_code(400);
      $pdo->rollBack();
      ob_clean();
      echo json_encode(['error' => 'Invalid is_active'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $set[] = "is_active = :is_active";
    $params[':is_active'] = $ia;
  }

  
  $photoUrl = $current['photo_url'] ?? null;
  $savedAbsPath = null;

  $photoDelete = (int)($_POST['photo_delete'] ?? 0) === 1;

  if ($photoDelete) {
    $set[] = "photo_url = NULL";
    if (is_string($photoUrl) && $photoUrl !== '' && str_starts_with($photoUrl, '/images/')) {
      $oldAbs = dirname(__DIR__) . $photoUrl;
      if (is_file($oldAbs)) @unlink($oldAbs);
    }
    $photoUrl = null;
  }

  if (isset($_FILES['photo']) && is_array($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
      http_response_code(400);
      $pdo->rollBack();
      ob_clean();
      echo json_encode(['error' => 'Photo upload failed'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['photo']['tmp_name']);
    $allow = [
      'image/jpeg' => 'jpg',
      'image/png'  => 'png',
      'image/webp' => 'webp',
    ];
    if (!isset($allow[$mime])) {
      http_response_code(400);
      $pdo->rollBack();
      ob_clean();
      echo json_encode(['error' => 'Invalid photo type'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $relDir = "/images/medications/{$memberId}";
    $absDir = dirname(__DIR__) . $relDir;

    if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) {
      http_response_code(500);
      $pdo->rollBack();
      ob_clean();
      echo json_encode(['error' => 'Cannot create upload dir'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allow[$mime];
    $savedAbsPath = $absDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $savedAbsPath)) {
      http_response_code(500);
      $pdo->rollBack();
      ob_clean();
      echo json_encode(['error' => 'Cannot save photo'], JSON_UNESCAPED_UNICODE);
      exit;
    }

    if (is_string($photoUrl) && $photoUrl !== '' && str_starts_with($photoUrl, '/images/')) {
      $oldAbs = dirname(__DIR__) . $photoUrl;
      if (is_file($oldAbs)) @unlink($oldAbs);
    }

    $photoUrl = $relDir . '/' . $filename;
    $set[] = "photo_url = :photo_url";
    $params[':photo_url'] = $photoUrl;
  }

  if (!empty($set)) {
    $sqlUpd = "
      UPDATE medications
      SET " . implode(", ", $set) . "
      WHERE medication_id = :mid AND member_id = :member_id
      LIMIT 1
    ";
    $stmt = $pdo->prepare($sqlUpd);
    $stmt->execute($params);
  }

  
  $map = [
    'morning'   => 'MORNING',
    'noon'      => 'NOON',
    'afternoon' => 'AFTERNOON',
    'evening'   => 'EVENING',
    'bedtime'   => 'BEDTIME',
  ];

  $scheduleTouched = 0;

  $stmtFind = $pdo->prepare("
    SELECT schedule_id
    FROM medication_schedules
    WHERE medication_id = :mid AND time_slot = :slot
    ORDER BY schedule_id ASC
    LIMIT 1
  ");

  $stmtUpdSch = $pdo->prepare("
    UPDATE medication_schedules
    SET instruction = :instruction, dose_qty = :dose_qty
    WHERE medication_id = :mid AND time_slot = :slot
  ");

  $stmtInsSch = $pdo->prepare("
    INSERT INTO medication_schedules (medication_id, time_slot, instruction, dose_qty)
    VALUES (:mid, :slot, :instruction, :dose_qty)
  ");

  foreach ($map as $prefix => $slot) {
    $kIns  = "{$prefix}_instruction";
    $kDose = "{$prefix}_dose_qty";

    if (!$has($kIns) && !$has($kDose)) continue;

    $instruction = strtoupper(trim((string)($_POST[$kIns] ?? '')));
    $doseRaw     = trim((string)($_POST[$kDose] ?? ''));

    $isNone = ($instruction === '' || $instruction === 'NONE');

    $stmtFind->execute([':mid' => $medicationId, ':slot' => $slot]);
    $exists = $stmtFind->fetch(PDO::FETCH_ASSOC);

    if ($isNone) {
      if ($exists) {
        $stmtUpdSch->execute([
          ':instruction' => null,
          ':dose_qty'    => null,
          ':mid'         => $medicationId,
          ':slot'        => $slot,
        ]);
        $scheduleTouched++;
      }
      continue;
    }

    if (!in_array($instruction, ['BEFORE_MEAL','AFTER_MEAL','ANY','BEDTIME'], true)) {
      http_response_code(400);
      $pdo->rollBack();
      if ($savedAbsPath && is_file($savedAbsPath)) @unlink($savedAbsPath);
      ob_clean();
      echo json_encode(['error' => "Invalid instruction: {$slot}"], JSON_UNESCAPED_UNICODE);
      exit;
    }

    if ($doseRaw === '' || !is_numeric($doseRaw)) {
      http_response_code(400);
      $pdo->rollBack();
      if ($savedAbsPath && is_file($savedAbsPath)) @unlink($savedAbsPath);
      ob_clean();
      echo json_encode(['error' => "Invalid dose_qty: {$slot}"], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $doseQty = (float)$doseRaw;
    if ($doseQty <= 0) {
      http_response_code(400);
      $pdo->rollBack();
      if ($savedAbsPath && is_file($savedAbsPath)) @unlink($savedAbsPath);
      ob_clean();
      echo json_encode(['error' => "Invalid dose_qty: {$slot}"], JSON_UNESCAPED_UNICODE);
      exit;
    }

    if ($exists) {
      $stmtUpdSch->execute([
        ':instruction' => $instruction,
        ':dose_qty'    => $doseQty,
        ':mid'         => $medicationId,
        ':slot'        => $slot,
      ]);
    } else {
      $stmtInsSch->execute([
        ':mid'         => $medicationId,
        ':slot'        => $slot,
        ':instruction' => $instruction,
        ':dose_qty'    => $doseQty,
      ]);
    }

    $scheduleTouched++;
  }

  $pdo->commit();

  ob_clean();
  echo json_encode([
    'data' => [
      'medication_id'    => $medicationId,
      'photo_url'        => $photoUrl,
      'schedule_touched' => $scheduleTouched
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  if (!empty($savedAbsPath) && is_file($savedAbsPath)) @unlink($savedAbsPath);

  http_response_code(500);
  ob_clean();
  echo json_encode(['error' => 'Server Error'], JSON_UNESCAPED_UNICODE);
}