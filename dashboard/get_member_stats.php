<?php
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

header("Content-Type: application/json; charset=UTF-8");

// 只允許 GET 請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode([
    'success' => false,
    'message' => '只允許 GET 請求'
  ], JSON_UNESCAPED_UNICODE);
  exit();
}

try {
  // 🔥 1. 會員總數
  $sqlTotal = "SELECT COUNT(*) as total FROM members";
  $stmtTotal = $pdo->query($sqlTotal);
  $totalMembers = (int)$stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

  // 🔥 2. 本月新增會員數
  $sqlThisMonth = "
        SELECT COUNT(*) as count 
        FROM members 
        WHERE YEAR(created_at) = YEAR(CURDATE()) 
        AND MONTH(created_at) = MONTH(CURDATE())
    ";
  $stmtThisMonth = $pdo->query($sqlThisMonth);
  $newMembersThisMonth = (int)$stmtThisMonth->fetch(PDO::FETCH_ASSOC)['count'];

  // 成功回應
  http_response_code(200);
  echo json_encode([
    'success' => true,
    'data' => [
      'total_members' => $totalMembers,
      'new_members_this_month' => $newMembersThisMonth,
      'new_members_today' => $newMembersToday,
      'gender_distribution' => $genderDistribution,
      'role_distribution' => $roleDistribution,
      'status_distribution' => $statusDistribution
    ],
    'message' => '成功取得會員統計資料'
  ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
  // 資料庫錯誤
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => '資料庫錯誤',
    'error' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}
