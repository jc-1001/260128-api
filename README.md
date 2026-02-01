# UniCare Api 後端

### 食用說明

<p>在自己負責的頁面建立一個資料夾</p>
<p>例如:我是負責藥品燈箱新增修改刪除，我需要建立一個"藥品燈箱"的資料夾，資料夾底下通常會有:(這裡先用中文代表檔名)</p>
<ul>
<li>get_藥品燈箱.php</li>
<li>post_藥品燈箱.php</li>
<li>put_藥品燈箱.php</li>
<li>delete_藥品燈箱.php</li>
</ul>
<p>依此類推 .......</p>

### 引入API連結的方法
<p>大家都會在前台寫一個API的連結，大部分都會長這樣:</p>

<code>const res = await fetch('http://localhost:8888/unicare_api/announcements/get_ann.php')</code>

<p>是可以正常運行的，但是只能在本地端，之後打包會很可怕，所以我在前端(前後台)的齒輪裡各寫一個:</p>

### .env(不會打包版)

<code>VITE_API_DOMAIN=http://localhost:8888/unicare_api/</code>

### .env.prod(會打包版)

<code>VITE_API_DOMAIN=https://tibamef2e.com/cjd102/g1/php/</code>

<p>所以只要將</p>

<code>const res = await fetch('http://localhost:8888/unicare_api/announcements/get_ann.php')</code>

<p>變成</p>

<code>res = await publicApi.post(
        import.meta.env.VITE_API_DOMAIN + 'announcements/add_ann_admin.php',
        payload,)</code>

<p>就可以正常運行它會自動轉換成伺服器的網址喔~</p>

<h2>import.meta.env.VITE_API_DOMAIN</h2>

<br>
<hr>
<br>
```
/unicare_api
├── /announcements          # 系統公告模組
│   ├── add_ann_admin.php    # 新增公告 (管理端編輯)
│   ├── delete_ann_admin.php # 刪除公告 (管理端編輯)
│   ├── get_ann_admin.php    # 取得公告列表 (管理端列表頁)
│   ├── get_ann_detail.php   # 取得公告詳細內容
│   ├── get_ann.php          # 取得公告列表 (前台顯示)
│   └── update_ann_admin.php # 更新公告 (管理端)
├── /common                 # 公用組件(大家要引入!!!)
│   ├── connect_cjd102g1.php # 資料庫連線設定
│   └── cors.php             # 跨域請求 (CORS) 設定
├── /home_modal             # 首頁燈箱模組
│   ├── get_latest_metrics.php # 取得最新一筆的身體數值
│   ├── get_member_header.php  # 會員表頭資訊及時間問候
│   └── save_metrics.php       # 將首頁新增的數值加進資料庫
└── /notifications          # 個人訊息通知(小鈴鐺)
    ├── get_notification.php # 取得通知列表
    └── mark_as_read.php     # 標記通知為已讀