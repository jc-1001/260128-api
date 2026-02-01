<?php
// 抓首頁會員的稱謂以及先生小姐
require_once __DIR__ . '/../common/cors.php';
require_once __DIR__ . '/../common/connect_cjd102g1.php';

// 之後要改
$member_id = 1;

$sql = "SELECT full_name, gender FROM Members WHERE member_id = :mid";
$stmt = $pdo->prepare($sql);
$stmt->execute(['mid' => $member_id]);
$member = $stmt->fetch();

if ($member) {
    // 取得姓氏(全名full_name的第一個字)
    $lastName = mb_substr($member['full_name'], 0, 1);
    // 預設值(如果是O)
    $title = "您好";

    // 先生/小姐判斷(M/F/O) 
    if ($member['gender'] === 'M') {
        $title = "先生";
    } else if ($member['gender'] === 'F') {
        $title = "小姐";
    } else {
        // 如果是 O ，不顯示姓氏，只顯示您好(有禮貌~~)
        $lastName = "";
        $title = "您好";
    }
    echo json_encode([
        'lastName' => $lastName,
        'title' => $title
    ]);
} else {
    
    echo json_encode([
        'lastName' => '訪客',
        'title' => '您好'
    ]);
}
