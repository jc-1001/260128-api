-- 建立資料庫 (若尚未建立)
--CREATE DATABASE IF NOT EXISTS unicare_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--USE unicare_db;

-- 建立會員資料表
CREATE TABLE Members (
    member_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '會員編號',
    email VARCHAR(255) NOT NULL UNIQUE COMMENT '電子信箱',
    password VARCHAR(255) NOT NULL COMMENT '密碼',
    full_name VARCHAR(100) COMMENT '姓名',
    phone_number VARCHAR(20) COMMENT '電話',
    gender ENUM('M', 'F', 'O') COMMENT '性別 (M:男, F:女, O:其他)',
    birth_date DATE COMMENT '生日',
    role VARCHAR(20) DEFAULT 'member' COMMENT '身分角色',
    account_status TINYINT DEFAULT 1 COMMENT '帳號狀態 (0:停用, 1:正常)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    last_login_at DATETIME COMMENT '最後登入時間',
    height DECIMAL(5,2) COMMENT '身高',
    weight DECIMAL(5,2) COMMENT '體重',
    blood_type VARCHAR(5) COMMENT '血型',
    has_chronic_disease BOOLEAN DEFAULT FALSE COMMENT '有無慢性病',
    chronic_disease_description VARCHAR(1000) COMMENT '慢性病描述',
    has_family_history BOOLEAN DEFAULT FALSE COMMENT '有無家族病史',
    family_history_description VARCHAR(1000) COMMENT '家族病史描述',
    has_allergies BOOLEAN DEFAULT FALSE COMMENT '有無過敏',
    allergy_description VARCHAR(1000) COMMENT '過敏描述',
    is_smoking BOOLEAN DEFAULT FALSE COMMENT '有無抽菸',
    is_drinking BOOLEAN DEFAULT FALSE COMMENT '有無飲酒',
    current_points INT DEFAULT 0 COMMENT '目前積分',
    points_updated_at DATETIME COMMENT '積分更新時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入一筆範例資料
INSERT INTO Members (email, password, full_name, gender, current_points) 
VALUES ('test@example.com', 'hashed_password_here', '王小明', 'M', 100);

-- 建立管理員表格 (Admins)
CREATE TABLE IF NOT EXISTS Admins (
    admin_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '管理員編號',
    email VARCHAR(255) NOT NULL COMMENT '電子信箱',
    password VARCHAR(20) NOT NULL COMMENT '密碼',
    admin_name VARCHAR(20) NOT NULL COMMENT '管理員名稱',
    last_login_at DATETIME NULL COMMENT '最後登入時間'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入一筆範例資料
INSERT INTO Admins (email, password, admin_name, last_login_at) 
VALUES ('admin@unicare.com', 'admin123', '總管理員', NOW());

-- 建立系統公告表格 (Announcements)
CREATE TABLE IF NOT EXISTS Announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '公告編號',
    created_by_admin_id INT NOT NULL COMMENT '建立管理員編號',
    title VARCHAR(50) NOT NULL COMMENT '標題',
    announcement_type VARCHAR(5) NOT NULL COMMENT '訊息類型',
    start_at DATETIME NULL COMMENT '公告開始時間',
    end_at DATETIME NULL COMMENT '公告結束時間',
    status ENUM('upload', 'down', 'schedule', 'draft') COMMENT '是否上架',
    content VARCHAR(1000) NOT NULL COMMENT '內容',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    FOREIGN KEY (created_by_admin_id) REFERENCES Admins(admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入一筆範例資料
INSERT INTO Announcements (
    created_by_admin_id, 
    title, 
    announcement_type, 
    start_at, 
    end_at, 
    status, 
    content
) VALUES (
    1, 
    '系統春節維護公告', 
    'INFO', 
    '2026-02-01 00:00:00', 
    '2026-02-05 23:59:59', 
    'upload', 
    '親愛的用戶，系統將於春節期間進行伺服器升級，屆時部分功能可能暫停使用。'
);

-- 建立個人通知表格 (Notifications)
CREATE TABLE IF NOT EXISTS Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY COMMENT '個人通知編號',
    member_id INT NOT NULL COMMENT '會員編號',
    type VARCHAR(10) NOT NULL COMMENT '類型',
    order_id INT NULL COMMENT '訂單編號',
    point_log_id INT NULL COMMENT '積分明細編號',
    title VARCHAR(50) NOT NULL COMMENT '標題',
    content VARCHAR(1000) NOT NULL COMMENT '內容',
    is_read BOOLEAN NOT NULL DEFAULT FALSE COMMENT '是否閱讀',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '送出時間',
    FOREIGN KEY (member_id) REFERENCES Members(member_id) ON DELETE CASCADE
    -- 注意：若您尚未建立 Orders 或 Point_Transactions 表，可先註解掉下方的外來鍵限制
    -- FOREIGN KEY (order_id) REFERENCES Orders(order_id),
    -- FOREIGN KEY (point_log_id) REFERENCES Point_Transactions(point_log_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 插入一筆範例資料
INSERT INTO Notifications (
    member_id, 
    type, 
    title, 
    content, 
    is_read
) VALUES (
    1, 
    'SYSTEM', 
    '歡迎加入 UniCare！', 
    '您的帳號已成功啟動，現在可以開始紀錄您的健康數值。', 
    0
);

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`) VALUES
(1, '骨骼關節保養'),
(2, '心血管循環'),
(3, '晶亮護眼');

-- --------------------------------------------------------

--
-- 資料表結構 `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `member_id` int(11) NOT NULL,
  `recipient_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_address` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `payment_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_info` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_fee` int(11) NOT NULL DEFAULT '100',
  `discount` int(11) NOT NULL DEFAULT '0',
  `total` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '訂單成立',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_spec` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `point_transactions`
--

CREATE TABLE `point_transactions` (
  `point_log_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `source` tinyint(4) NOT NULL,
  `points_change` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spec` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` int(11) NOT NULL,
  `tag` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `features` json NOT NULL,
  `details` json NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `is_on_shelf` tinyint(1) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `image`, `title`, `spec`, `price`, `tag`, `keywords`, `description`, `features`, `details`, `stock_quantity`, `is_on_shelf`, `created_at`, `updated_at`) VALUES
(1, 1, 'images/shop/product_01.jpg', '海洋鈣鎂D+強效錠', '60錠 / 袋', 480, '熱銷冠軍', '鈣片, 補鈣, 睡覺, 抽筋, 牛奶, 骨頭', '嚴選愛爾蘭紅藻鈣，多孔結構吸收佳。搭配鎂與D3，穩固骨骼基石，睡前補充助入睡。', '[\"專利愛爾蘭海藻鈣：源自純淨海域，多孔性結構，吸收率高達 39.5%。\", \"黃金比例配方：鈣與鎂 2:1 完美比例，協同作用效果更佳。\", \"增量維生素 D3：每份添加 400IU，大幅提升鈣質吸收效率。\", \"添加 CPP 酪蛋白：鎖住鈣質不流失，全家人補鈣首選。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 2 錠，建議於睡前食用，幫助入睡與鈣質吸收。\", \"dosage\": \"錠劑\", \"warning\": \"本產品含有牛奶及其製品，不適合對其過敏體質者食用。\", \"quantity\": \"60 錠/袋\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(2, 1, 'images/shop/product_02.jpg', '葡萄糖胺軟骨素複方', '90錠 / 袋', 820, '長輩首選', '膝蓋, 爬樓梯, 軟骨, 關節, 走路, 孝親', '行動力升級！葡萄糖胺結合軟骨素，雙重滋補關鍵。添加貓爪藤，上下樓梯潤滑不卡關。', '[\"雙重關鍵原料：高純度葡萄糖胺鹽酸鹽＋鯊魚軟骨素，提供潤滑與彈性。\", \"貓爪藤萃取物：亞馬遜雨林的天然草本，溫和舒緩關鍵不適。\", \"維生素 C 加乘：促進膠原蛋白形成，有助於傷口癒合與結締組織生長。\", \"長輩保養首選：適合隨年齡增長感到僵硬、活動不順暢的族群。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 3 錠，可分次食用，建議飯後搭配溫開水。\", \"dosage\": \"錠劑\", \"warning\": \"本產品含甲殼類、魚類製品，孕婦及哺乳期婦女使用前請諮詢醫師。\", \"quantity\": \"90 錠/袋\"}', 3, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(3, 1, 'images/shop/product_03.jpg', 'UC-II膠原蛋白', '30膠囊 / 袋', 1350, '', '膠原蛋白, 運動, 健身, 關節, 跑步', '美國專利UC-II®，低溫萃取保留活性。每日一顆，保護力勝葡萄糖胺兩倍，修復關鍵。', '[\"美國專利 UC-II®：擁有哈佛醫學院等多項研究支持，40mg 足量添加。\", \"低溫萃取技術：完整保留三股螺旋活性結構，確保有效利用。\", \"高純度玻尿酸：來自流行鏈球菌發酵，補水潤滑，舒適度再升級。\", \"橄欖果實萃取：富含多酚物質，調節生理機能，提升保護力。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 1 粒，空腹食用效果最佳（建議早餐前）。\", \"dosage\": \"膠囊\", \"warning\": \"兒童、孕婦、哺乳婦女及服用藥物者，食用前請先諮詢醫療人員。\", \"quantity\": \"30 粒/袋\"}', 0, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(4, 1, 'images/shop/product_04.jpg', '靈活關節養護配方', '60錠 / 袋', 650, '全素可食', '素食, 薑黃, MSM, 關節痛, 久站', '久站久坐首選！高純度MSM搭配薑黃，有效舒緩沉重感，告別僵硬，找回輕鬆自在節奏。', '[\"99.9% 高純度 MSM：美國大廠原料，形成軟骨組織的重要關鍵成分。\", \"薑黃素精華：天然的代謝推進器，幫助舒緩緊繃，增強體力。\", \"黑胡椒萃取 (BioPerine)：臨床證實可提升薑黃素吸收率達 2000%。\", \"全素可食：不含動物性成分，素食者也能安心保養關鍵部位。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 2 錠，建議飯後食用，以利薑黃吸收。\", \"dosage\": \"錠劑\", \"warning\": \"避免睡前食用，孕婦及哺乳期婦女不建議食用 MSM。\", \"quantity\": \"60 錠/袋\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(5, 1, 'images/shop/product_05.jpg', 'HMB肌力修復膠囊', '60膠囊 / 袋', 1180, '長輩首選', '肌肉, 肌少症, 老人, 跌倒, 蛋白質', '鎖住肌肉力量！HMB搭配維生素D與BCAA，穩固下盤，預防跌倒，讓日常行走更有力。', '[\"關鍵 HMB-Ca：每份含 1500mg，相當於補充大量雞蛋與牛肉的精華。\", \"肌力黃金三角：HMB + BCAA + 維生素 D3，全方位維持肌肉生理功能。\", \"穩固防跌：特別適合肌少風險族群、術後恢復期或高齡長輩。\", \"好吞食膠囊：無特殊異味，輕鬆補充每日所需的肌肉營養。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 2 粒，運動後食用或與正餐同時食用效果佳。\", \"dosage\": \"膠囊\", \"warning\": \"本品含乳製品（若BCAA來源為乳清），特殊體質請先諮詢醫師。\", \"quantity\": \"60 粒/袋\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(6, 1, 'images/shop/product_06.jpg', 'MSM舒緩鎮痛配方', '45錠 / 袋', 580, '', '止痛, 舒緩, 鳳梨酵素, 運動傷害, 拉傷', '急性不適加強版！高劑量MSM添加鳳梨酵素，針對強烈緊繃快速舒緩，運動恢復必備。', '[\"加強型 MSM 配方：濃度提升，針對卡關不順提供強效支援。\", \"鳳梨酵素 (Bromelain)：高活性蛋白質分解酵素，幫助代謝、緩解不適。\", \"乳香萃取物 (Boswellia)：傳承千年的古老智慧，著名的舒緩草本成分。\", \"運動員推薦：適合馬拉松、登山或高強度訓練後的恢復期使用。\"]', '{\"days\": \"15 天 (加強期)\", \"usage\": \"每日 3 錠，可於不適感強烈時分次食用。\", \"dosage\": \"錠劑\", \"warning\": \"本品含鳳梨酵素，服用抗凝血藥物者請先諮詢醫師。\", \"quantity\": \"45 錠/袋\"}', 0, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(7, 2, 'images/shop/product_07.jpg', '頂級深海rTG魚油', '60軟膠囊 / 罐', 1280, '熱銷冠軍', '魚油, omega-3, 血管, 腦部, 記憶力, 考試', '先進rTG萃取，吸收率倍增。85%高濃度Omega-3，調節機能，思緒清晰代謝順暢。', '[\"85% 高濃度 Omega-3：EPA+DHA 黃金比例，一顆抵多顆。\", \"頂級 rTG 型態：模擬天然結構，吸收率是一般魚油的 2 倍。\", \"純淨小型魚種：嚴選南太平洋鯷魚，無重金屬汙染疑慮。\", \"IFOS 五星認證：通過國際最高標準檢驗，新鮮度與純度有保障。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 2 粒，建議隨餐或飯後食用，吸收效果最佳。\", \"dosage\": \"軟膠囊 (Softgel)\", \"warning\": \"正在服用抗凝血劑者（如阿斯匹靈），食用前請諮詢醫師。\", \"quantity\": \"60 粒/罐\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(8, 2, 'images/shop/product_08.jpg', '還原型Q10能量軟膠囊', '30軟膠囊 / 袋', 890, '夜間保養', '心臟, 能量, 疲勞, 備孕, 皮膚', '日本Kaneka還原型Q10，直接吸收無須轉換。啟動能量發電廠，找回年輕體力，氣色紅潤。', '[\"日本 Kaneka 原廠：全球最大 Q10 供應商，酵母發酵製程，品質純淨。\", \"還原型 (Ubiquinol)：人體利用率高，非一般氧化型需轉化。\", \"複方維生素 B+E：協同抗氧化作用，增進皮膚與血球健康。\", \"青春能量之鑰：適合備孕調理、高壓工作者或熟齡保養。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 1 粒，建議早餐後食用，啟動一日活力。\", \"dosage\": \"軟膠囊\", \"warning\": \"15歲以下小孩、懷孕或哺乳期間婦女及服用抗凝血藥品(Warfarin)之病患，不宜食用。\", \"quantity\": \"30 粒/袋\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(9, 2, 'images/shop/product_09.jpg', '納豆紅麴暢通配方', '60膠囊 / 罐', 950, '夜間保養', '膽固醇, 血脂, 納豆, 紅麴, 循環, 晚上吃', '專為循環負擔設計。高活性納豆激酶加紅麴，把握夜間修復期，清理堆積，維持管道暢通。', '[\"高活性納豆激酶：每份含 2000FU，有效幫助溶解堆積物。\", \"優質紅麴萃取：含有活性成分 Monacolin K，調節生理機能。\", \"夜間黃金保養：建議睡前食用，符合人體循環代謝時鐘。\", \"橘黴素未檢出：通過嚴格檢驗，不含橘黴素 (Citrinin)，安心食用。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 2 粒，建議晚餐後或睡前食用。\", \"dosage\": \"膠囊\", \"warning\": \"本品含大豆製品。避免與葡萄柚同時食用；服用降血脂藥或抗凝血藥者，請諮詢醫師。\", \"quantity\": \"60 粒/罐\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(10, 2, 'images/shop/product_10.jpg', '南極純淨磷蝦油', '60軟膠囊 / 罐', 1150, '', '蝦紅素, 氣色, 生理期, 磷蝦油, 抗氧化', '南極純淨紅寶石。特殊磷脂質結構更好吸收，天然蝦紅素帶來好氣色，無魚腥味好入口。', '[\"獨特磷脂質結構：兼具親水親油特性，進入體內快速吸收利用。\", \"天然蝦紅素：賦予磷蝦油紅寶石色澤，強效抗氧化保護。\", \"純淨無汙染：來自南極極淨海域，位於食物鏈底層，無生物累積毒素。\", \"女性保養首選：幫助調節每月不適，維持紅潤好氣色。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 2 粒，隨餐食用。\", \"dosage\": \"軟膠囊\", \"warning\": \"本品含甲殼類製品，對其過敏者請勿食用。服用抗凝血劑者請諮詢醫師。\", \"quantity\": \"60 粒/罐\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(11, 2, 'images/shop/product_11.jpg', 'L-精氨酸循環加強錠', '90錠 / 罐', 780, '', '男性, 精力, 運動, 循環, 手腳冰冷', '一氧化氮關鍵前驅物，擴充通道提升效率。推薦運動或男性保養，搭配鋅與馬卡，爆發力十足。', '[\"NO 一氧化氮生成：精氨酸是重要原料，幫助放鬆管道，促進流動。\", \"鋅 (Zinc) 添加：有助於維持生長發育與生殖機能。\", \"黑馬卡萃取：祕魯國寶，滋補強身，提升運動表現與耐力。\", \"循環與代謝：改善手腳冰冷問題，提升整體精氣神。\"]', '{\"days\": \"30-45 天\", \"usage\": \"每日 2-3 錠，空腹或睡前食用效果最佳。\", \"dosage\": \"錠劑\", \"warning\": \"皰疹患者或正服用心血管藥物者，請諮詢醫師後再食用。\", \"quantity\": \"90 錠/罐\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(12, 2, 'images/shop/product_12.jpg', '高濃縮無味大蒜精華', '45錠 / 袋', 550, '長輩首選', '大蒜, 免疫力, 感冒, 殺菌, 抵抗力', '冷壓濃縮保留營養，去除刺鼻異味！天然防護罩增強保護力。小小一顆等於五千毫克新鮮大蒜。', '[\"無味大蒜精：特殊去味技術，只有營養沒有口臭，社交不尷尬。\", \"高單位濃縮：濃縮比高，輕鬆攝取大蒜素 (Allicin)。\", \"調整體質：增強身體防禦系統，換季期間的最佳盟友。\", \"促進新陳代謝：含硫化合物，幫助身體循環代謝。\"]', '{\"days\": \"60 天\", \"usage\": \"每日 2 粒，飯後食用。\", \"dosage\": \"軟膠囊\", \"warning\": \"動手術前後兩週或有凝血功能異常者，請暫停食用。\", \"quantity\": \"120 粒/罐\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(13, 3, 'images/shop/product_13.jpg', '游離型葉黃素複方膠囊', '30膠囊 / 袋', 980, '上班族必備', '眼睛, 葉黃素, 藍光, 手機, 電腦, 乾澀', '美國專利FloraGLO®游離型葉黃素，10:2黃金比例。過濾有害光線，隱形防護罩，3C族晶亮首選。', '[\"專利游離型：分子小、好吸收，直接利用無須轉換。\", \"黃金比例 10:2：依照美國國家衛生院 (NEI) 建議配方設計。\", \"山桑子添加：輔助舒緩疲勞，提升整體舒適度。\", \"3C 族群必備：針對藍光傷害提供全天候的隱形保護。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 1 粒，建議飯後食用（葉黃素為脂溶性）。\", \"dosage\": \"膠囊\", \"warning\": \"兒童、孕婦、哺乳者，使用前請諮詢醫療人員。\", \"quantity\": \"30 粒/袋\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(14, 3, 'images/shop/product_14.jpg', '北歐野生山桑子精華', '60膠囊 / 袋', 850, '', '夜盲, 開車, 花青素, 藍莓, 飛行員', '飛行員的秘密！北歐野生山桑子，十倍花青素含量。適應光線變化，夜間開車、閱讀依然清晰銳利。', '[\"高濃度花青素：超越一般藍莓，擁有強大的傳導與循環能力。\", \"暗處視覺支援：幫助合成視紫質，提升夜間或低光環境的視覺品質。\", \"促進循環：改善末梢循環，讓養分能順利輸送至靈魂之窗。\", \"天然抗氧化：對抗自由基傷害，延緩老化與退化風險。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 2 粒，早晚各 1 粒，飯後食用。\", \"dosage\": \"膠囊\", \"warning\": \"本產品為天然植物萃取，如有色差屬正常現象。\", \"quantity\": \"60 粒/袋\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(15, 3, 'images/shop/product_15.jpg', '紅藻蝦紅素護明軟膠囊', '45錠 / 袋', 1080, '全素可食', '素食, 蝦紅素, 眼睛酸, 對焦, 放鬆', '超級維生素蝦紅素，保護力勝維他命C六千倍。深入調節核心，極速舒緩酸澀緊繃，找回靈活對焦。', '[\"雨生紅球藻萃取：純植物來源，素食者可食用的天然蝦紅素。\", \"極速舒緩感：針對長時間用眼造成的肌肉緊繃，提供快速放鬆。\", \"靈活對焦力：幫助調節焦距，改善看近看遠轉換時的模糊感。\", \"超級抗氧化劑：強效捕捉自由基，全方位守護晶亮健康。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 1 粒，建議飯後食用。\", \"dosage\": \"軟膠囊\", \"warning\": \"12歲以下兒童、孕婦、哺乳婦女不宜食用。\", \"quantity\": \"30 粒/袋\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(16, 3, 'images/shop/product_16.jpg', '高濃度DHA晶亮魚油', '60軟膠囊 / 罐', 1120, '長輩首選', '兒童, 乾眼, 魚油, DHA, 水潤', '專為水潤設計，高比例DHA鎖住水分。提升油層穩定，減少乾澀不適。清透小顆粒，兒童也可安心食。', '[\"高含量 DHA：構成明亮視界的重要成分，比例高於 EPA。\", \"鎖水保濕：強化油層結構，減少水分揮發，對抗乾澀沙漠感。\", \"好吞食設計：小顆粒軟膠囊，無魚腥味，適合兒童與吞嚥困難者。\", \"玻尿酸添加：口服級玻尿酸，由內而外補水，水潤感再升級。\"]', '{\"days\": \"30 天\", \"usage\": \"每日 2 粒，隨餐食用。兒童可咬破食用。\", \"dosage\": \"軟膠囊\", \"warning\": \"凝血功能不佳者，食用前請先詢問醫師。\", \"quantity\": \"60 粒/罐\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06'),
(17, 3, 'images/shop/product_17.jpg', '全方位晶亮綜合維他命', '60錠 / 罐', 680, '熱銷冠軍', '維他命, 綜合, 枸杞, 決明子, 便宜, CP值', '基礎保養一瓶搞定！維生素A、C、E結合枸杞決明子。維持暗處視覺，中西合併，全家人溫和滋補。', '[\"基礎營養打底：提供晶亮健康所需的維生素群與礦物質鋅。\", \"中西草本複方：結合西方營養學與東方枸杞、決明子智慧。\", \"維持暗處視覺：維生素 A 有助於維持在暗處的視覺功能。\", \"高 CP 值選擇：一瓶滿足全家大小的日常保養需求，經濟實惠。\"]', '{\"days\": \"30-60 天\", \"usage\": \"每日 1-2 錠，飯後食用。\", \"dosage\": \"錠劑\", \"warning\": \"本品含多種維生素，尿液變黃屬正常現象。\", \"quantity\": \"60 錠/罐\"}', 20, 1, '2026-01-25 19:36:06', '2026-01-25 19:36:06');

-- --------------------------------------------------------

--
-- 資料表結構 `product_gallery`
--

CREATE TABLE `product_gallery` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `large_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `small_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 傾印資料表的資料 `product_gallery`
--

INSERT INTO `product_gallery` (`image_id`, `product_id`, `large_url`, `small_url`) VALUES
(1, 1, 'images/shop/product_01_L.jpg', 'images/shop/small/product_01_S.jpg'),
(2, 1, 'images/shop/pill_01.jpg', 'images/shop/small/pill_01_S.jpg'),
(3, 1, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(4, 2, 'images/shop/product_02_L.jpg', 'images/shop/small/product_02_S.jpg'),
(5, 2, 'images/shop/pill_02.jpg', 'images/shop/small/pill_02_S.jpg'),
(6, 2, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(7, 3, 'images/shop/product_03_L.jpg', 'images/shop/small/product_03_S.jpg'),
(8, 3, 'images/shop/pill_03.jpg', 'images/shop/small/pill_03_S.jpg'),
(9, 3, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(10, 4, 'images/shop/product_04_L.jpg', 'images/shop/small/product_04_S.jpg'),
(11, 4, 'images/shop/pill_04.jpg', 'images/shop/small/pill_04_S.jpg'),
(12, 4, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(13, 5, 'images/shop/product_05_L.jpg', 'images/shop/small/product_05_S.jpg'),
(14, 5, 'images/shop/pill_05.jpg', 'images/shop/small/pill_05_S.jpg'),
(15, 5, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(16, 6, 'images/shop/product_06_L.jpg', 'images/shop/small/product_06_S.jpg'),
(17, 6, 'images/shop/pill_06.jpg', 'images/shop/small/pill_06_S.jpg'),
(18, 6, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(19, 7, 'images/shop/product_07_L.jpg', 'images/shop/small/product_07_S.jpg'),
(20, 7, 'images/shop/pill_01.jpg', 'images/shop/small/pill_01_S.jpg'),
(21, 7, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(22, 8, 'images/shop/product_08_L.jpg', 'images/shop/small/product_08_S.jpg'),
(23, 8, 'images/shop/pill_02.jpg', 'images/shop/small/pill_02_S.jpg'),
(24, 8, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(25, 9, 'images/shop/product_09_L.jpg', 'images/shop/small/product_09_S.jpg'),
(26, 9, 'images/shop/pill_03.jpg', 'images/shop/small/pill_03_S.jpg'),
(27, 9, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(28, 10, 'images/shop/product_10_L.jpg', 'images/shop/small/product_10_S.jpg'),
(29, 10, 'images/shop/pill_04.jpg', 'images/shop/small/pill_04_S.jpg'),
(30, 10, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(31, 11, 'images/shop/product_11_L.jpg', 'images/shop/small/product_11_S.jpg'),
(32, 11, 'images/shop/pill_05.jpg', 'images/shop/small/pill_05_S.jpg'),
(33, 11, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(34, 12, 'images/shop/product_12_L.jpg', 'images/shop/small/product_12_S.jpg'),
(35, 12, 'images/shop/pill_06.jpg', 'images/shop/small/pill_06_S.jpg'),
(36, 12, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(37, 13, 'images/shop/product_13_L.jpg', 'images/shop/small/product_13_S.jpg'),
(38, 13, 'images/shop/pill_01.jpg', 'images/shop/small/pill_01_S.jpg'),
(39, 13, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(40, 14, 'images/shop/product_14_L.jpg', 'images/shop/small/product_14_S.jpg'),
(41, 14, 'images/shop/pill_02.jpg', 'images/shop/small/pill_02_S.jpg'),
(42, 14, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(43, 15, 'images/shop/product_15_L.jpg', 'images/shop/small/product_15_S.jpg'),
(44, 15, 'images/shop/pill_03.jpg', 'images/shop/small/pill_03_S.jpg'),
(45, 15, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(46, 16, 'images/shop/product_16_L.jpg', 'images/shop/small/product_16_S.jpg'),
(47, 16, 'images/shop/pill_04.jpg', 'images/shop/small/pill_04_S.jpg'),
(48, 16, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg'),
(49, 17, 'images/shop/product_17_L.jpg', 'images/shop/small/product_17_S.jpg'),
(50, 17, 'images/shop/pill_05.jpg', 'images/shop/small/pill_05_S.jpg'),
(51, 17, 'images/shop/nutrition_label_L.jpg', 'images/shop/small/nutrition_label.jpg');

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `member_id` (`member_id`);

--
-- 資料表索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- 資料表索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `member_id` (`member_id`);

--
-- 資料表索引 `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 資料表索引 `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD PRIMARY KEY (`point_log_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `order_id` (`order_id`);

--
-- 資料表索引 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- 資料表索引 `product_gallery`
--
ALTER TABLE `product_gallery`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `point_transactions`
--
ALTER TABLE `point_transactions`
  MODIFY `point_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `product_gallery`
--
ALTER TABLE `product_gallery`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 資料表的限制式 `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- 資料表的限制式 `point_transactions`
--
ALTER TABLE `point_transactions`
  ADD CONSTRAINT `point_transactions_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- 資料表的限制式 `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON UPDATE CASCADE;

--
-- 資料表的限制式 `product_gallery`
--
ALTER TABLE `product_gallery`
  ADD CONSTRAINT `product_gallery_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;