<?php

function sendNotification($pdo, $member_id, $title, $content) {
  try {
    $sql = "INSERT INTO notifications (member_id, type, title, content, is_read, created_at) VALUES (?, 'SYSTEM', ?, ?, 0, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$member_id, $title, $content]);
  } catch (Exception $e) {
    error_log("Notification Error: " . $e->getMessage());
  }
}

?>