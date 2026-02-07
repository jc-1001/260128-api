<?php
// LINE Pay 設定檔 - 格式示意檔

return [
    'channel_id' => '你的ID',
    'channel_secret' => '你的Secret',
    'version' => 'v3',
    'site_url' => 'https://sandbox-api-pay.line.me', // 沙盒環境網址
    
    // 這一行是用來設定「付款後跳回來的網址」
    // 本機前端開發網址 (http://localhost:5173)
    'return_url' => 'http://localhost:5173/payment/confirm', 
    'cancel_url' => 'http://localhost:5173/payment/cancel',
];