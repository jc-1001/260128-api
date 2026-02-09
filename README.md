# UniCare 健康數據中控台

![Project Status](https://img.shields.io/badge/Status-Development-yellow)
![Vue.js](https://img.shields.io/badge/Vue.js-35495E?style=flat&logo=vuedotjs&logoColor=4FC08D)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white)

![UniCare Logo](./src/assets/images/github_banner.png) 

> **遠端守護，聯合照顧** —— 結合醫療保健產品與長者慢性身體數據紀錄的全方位照護平台。

## 專案簡介

**UniCare** 是一套專為解決現代子女因工作忙碌、無法時刻陪伴長輩痛點所設計的系統。我們致力於改善傳統紙筆記錄易丟失、介面複雜無誘因等問題。

透過本平台，使用者可以進行全方位的數位健康數據管理。同時結合樂活商城，讓子女即便遠在他鄉，也能即時透過視覺化的圖表與通知，精準掌握家中長輩的健康狀況。

---

## 相關連結

* [專案正式上線網址](https://tibamef2e.com/cjd102/g1/front/)
* [專案 Demo 影片](https://www.google.com/search?q=https://youtube.com/...)
* [系統設計文件](https://www.google.com/search?q=https://...)
* [Figma連結](https://www.figma.com/design/X78x31tK2e6S5C17o5cA0N/%E5%9C%98%E9%AB%94%E5%B0%88%E9%A1%8C%E8%A8%AD%E8%A8%88%E6%AD%A3%E5%BC%8F-%E5%A4%9A%E9%A0%81%E9%9D%A2-?node-id=0-4&t=FtUkNAqiQ6REpRAA-1)

---

## 技術架構

### 前端
| 類別 | 技術/工具 |
| :--- | :--- |
| **核心框架** | ![Vue.js](https://img.shields.io/badge/Vue.js_3-35495E?logo=vue.js) ![Vite](https://img.shields.io/badge/Vite-646CFF?logo=vite&logoColor=white) |
| **樣式與 UI** | ![Sass](https://img.shields.io/badge/Sass-CC6699?logo=sass&logoColor=white) ![Element Plus](https://img.shields.io/badge/Element_Plus-409EFF?logo=element-plus&logoColor=white) |
| **動畫與互動** | Swiper.js, SweetAlert2 |
| **數據視覺化** | Chart.js |
| **API 與 邏輯** | Axios, Day.js |

### 後端 & 其他
| 類別 | 技術/工具 |
| :--- | :--- |
| **後端語言** | ![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white) |
| **資料庫** | ![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white) |
| **協作工具** | Git, SourceTree, Figma |

---

## 專案功能

### 前台功能 (使用者端)

| 頁面模組 | 功能亮點 |
| :--- | :--- |
| **首頁戰情室** | • **紅綠燈警示**：依健康標準顯示顏色提醒今日狀態。<br>• **即時通知**：藥品低庫存提示、服藥時間提醒。<br>• **快速紀錄**：燈箱式介面，快速輸入數值與飲食。 |
| **身體數值中心** | • **數據視覺化**：體重、血壓、血糖、血氧、心率折線圖。<br>• **趨勢分析**：支援切換 7 天及 30 天趨勢檢視。 |
| **數位藥箱** | • **互動翻卡**：點擊藥品卡片查看詳細成分與用法。<br>• **庫存管理**：視覺化呈現剩餘藥量。 |
| **飲食日記** | • **行事曆檢視**：以底色直觀區分每日紀錄狀態。<br>• **圖文紀錄**：支援上傳餐點照片與詳細文字描述。 |
| **樂活商城** | • **完整電商**：分類篩選、購物車、關鍵字搜尋。<br>• **金流串接**：整合 **LINE Pay** 支付功能。<br>• **訂單追蹤**：歷史訂單查詢與「再買一次」捷徑。<br>• **商城積分**：獲得方式 - 每日簽到、商城有獎徵答遊戲。|
| **個人中心** | 會員資料管理、積分查詢、幫助中心 (FAQ)。 |

### 後台功能 (管理端)

| 管理模組 | 功能亮點 |
| :--- | :--- |
| **儀表板** | 數據總覽 (使用者增長、營收)、圖表切換 (月/季/年)、熱銷排行。 |
| **使用者管理** | 管理使用者帳號狀態。 |
| **商品管理** | 商品上下架、庫存調整、商品介紹調整。 |
| **訂單管理** | 訂單狀態變更 (連動前台通知)、出貨管理、條件篩選。 |
| **系統管理** | 公告與優惠活動發布、權限控管。 |

---

## 專案架構

| 資料夾 | 說明 | GitHub連結 |
| :--- | :--- | :--- |
| `front/` | 前台內容 | [前台](https://github.com/jc-1001/251228_team_project/tree/dev) |
| `admin/` | 後台內容 | [後台](https://github.com/jc-1001/260110-backstage/tree/dev) |
| `api/` | PHP內容 | [API](https://github.com/jc-1001/260128-api/tree/dev) |

---

## 團隊成員

| 成員 | 負責領域 | 詳細職責 | GitHub |
| :--- | :--- | :--- | :---: |
| **游佳純** | 前台 / 後台 / 文件 | Layout 切版、Banner、快速紀錄、後台 Element Plus 導入、系統活動管理 | [@jc-1001](https://github.com/jc-1001) |
| **徐子益** | 前台 | 數位藥箱、服藥提醒、翻卡特效、庫存視覺化 | [@ziyi1114](https://github.com/ziyi1114-bot) |
| **羅方敏** | 前台 / 後台 | 登入註冊系統、幫助中心、個人資料、後台使用者管理 | [@neoLuo00c](https://github.com/neoLuo00c) |
| **劉岳霖** | 前台 | 飲食日記、行事曆整合 (Day.js) | [@yuelinnnnn](https://github.com/yuelinnnnn) |
| **李妮** | 前台 / 後台 / 金流 | 商城系統、購物車、LINE Pay 串接、訂單管理、商品管理、積分管理 | [@NLeeii](https://github.com/NLeeii) |
| **黃煜軒** | 前台 / 後台 | 身體數值中心 (Chart.js)、後台儀表板數據統計 | [@bruce3721180](https://github.com/bruce3721180) |

---

## 如何開始

1. **環境需求**： Node.js (v16+), npm 或 yarn。
2. **安裝依賴**：

```bash
# Clone 專案
git clone [您的專案網址]

# 進入資料夾並安裝
cd unicare-project
npm install

```

3. **啟動專案**：

```bash
npm run dev

```

請前往 `http://localhost:5173` 查看運行結果。

---

© 2026 UniCare Team. All Rights Reserved.