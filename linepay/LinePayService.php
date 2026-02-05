<?php

// 負責加密邏輯，提供其他檔案複用
class LinePayService {
  private $channelId;
  private $channelSecret;
  private $siteUrl;

  // __construct (建構子)，LinePayService() 的瞬間，第一個執行的程式
  public function __construct($config) {
    $this->channelId = $config['channel_id'];
    $this->channelSecret = $config['channel_secret'];
    $this->siteUrl = $config['site_url'];
  }

  // 發送請求給 LINE Pay API (通用函式)
  public function request($uri, $body = []) {
    $url = $this->siteUrl . $uri;
    $nonce = uniqid(); // 產生一個隨機字串
    $authJson = json_encode($body);

    // --- 產生 HMAC-SHA256 簽章 ---
    // 簽章公式：Channel Secret + URI + Request Body + nonce
    $data = $this->channelSecret . $uri . $authJson . $nonce;
    $hash = hash_hmac('sha256', $data, $this->channelSecret, true);
    $signature = base64_encode($hash);

    // --- 準備 Header ---
    $headers = [
      'Content-Type: application/json',
      'X-LINE-ChannelId: ' . $this->channelId,
      'X-LINE-Authorization-Nonce: ' . $nonce,
      'X-LINE-Authorization: ' . $signature
    ];

    // --- 發送 Curl 請求 ---
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $authJson);

    // 在本機開發時，忽略 SSL 憑證檢查 (解決 MAMP 報錯的主因)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);

    // 除錯用：如果 Curl 連線本身失敗 (例如沒網路)，這裡可以抓到
    if ($result === false) {
      throw new Exception('Curl error: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($result, true);

    // 簡單檢查回傳狀態
    if ($httpCode !== 200 || !isset($response['returnCode']) || $response['returnCode'] !== '0000') {
      // 可以在這裡記錄錯誤
      // error_log('LINE Pay Error: ' . $result);
      return [
        'success' => false,
        'message' => $response['returnMessage'] ?? 'LINE Pay API Error',
        'raw' => $response
      ];
    }

    return [
      'success' => true,
      'data' => $response['info']
    ];

  }
}