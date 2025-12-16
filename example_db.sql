--
-- 데이터베이스: `moroi`
--

-- --------------------------------------------------------

--
-- 테이블 구조 `accounts`
--

CREATE TABLE `accounts` (
  `id` int(10) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- 테이블의 덤프 데이터 `accounts`
--

INSERT INTO `accounts` (`id`, `username`, `password`, `created_at`, `updated_at`) VALUES
(1, 'moroi', '$2y$10$5AhIFgWtcGIulqcpzs/wIOVNPuPNMGBLV6GfLVLX5Uxa8jF5NrlRC', '2025-12-02 07:51:42', '2025-12-02 07:51:42');

-- --------------------------------------------------------

--
-- 테이블 구조 `products`
--

CREATE TABLE `products` (
  `id` int(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `vision_desc` varchar(2000) NOT NULL,
  `price` int(10) NOT NULL,
  `category` varchar(20) NOT NULL,
  `position` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- 테이블의 덤프 데이터 `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `vision_desc`, `price`, `category`, `position`) VALUES
(1, '오레오 딸기 크림맛', '오레오 딸기 크림 초콜릿 샌드위치 쿠키\r\n중량: 50g 봉지 두개로 총 100g\r\n행사: 2+1 행사 진행중', '파란색 길쭉한 종이 패키지에 핑크색 액센트 컬러 과자', 2000, '과자', '10'),
(2, '녹차 카테킨', '제조사:대웅제약\r\n브랜드:닥터베어\r\n용량:30g,총 30일분\r\n효능:체지방 감소, 혈중 콜레스테롤 개선\r\n행사:5000원 프로모션 진행중 (12월 10일까지)', '초록색의 작은 종이 패키징 6면중 앞면,윗면,옆면,아랫면은 초록색, 뒷면은 백색에 상품 정보 적혀 있음', 5000, '생필품', '3'),
(4, '타이레놀', '아세트아미노펜 단일 성분 해열 진통제\r\n용량: 500mg 10정\r\n특징: 위장 장애 부담이 적고 카페인이 없음', '흰색 배경에 빨간색 띠가 상단에 있는 직사각형 종이 상자. 상자 중앙에 굵은 검은색 글씨로 \'Tylenol\' 또는 \'타이레놀\'이라고 적혀 있음.', 3000, '생필품', '1'),
(5, '핑크 블루투스 스피커', '블루투스 스테레오 스피커, 유선 AUX 및 블루투스 3.0 재생 지원', '핑크색 종이 상자 패키지, 10cm x 5cm x 3cm 박스 크기, 전면에는 블루투스 스피커 상품 그림이 있으며 나머지 면은 핑크색', 25000, '생필품', '2'),
(6, '코카콜라 캔', '코카콜라 오리지널 맛. 250ml 용량의 슬림 캔.', '빨간색 알루미늄 캔. 캔 중앙에 흰색의 굵고 흐르는 듯한 필기체 로고 (Coca-Cola)가 사선으로 인쇄되어 있음. 캔 상단과 하단에는 은색(알루미늄) 부분이 약간 노출되어 있음', 1500, '음료수', '4'),
(7, '포카리스웨트 캔', '체내 수분 균형을 맞춰주는 이온 음료. 245ml 일반 캔 형태.', '흰색 바탕에 파란색과 하늘색이 혼합된 물결무늬 패턴이 상단에 디자인된 알루미늄 캔. 캔 중앙에 굵은 파란색 고딕체로 \'POCARI SWEAT\' 로고가 인쇄되어 있으며, 로고 하단에 245ml 용량이 표기되어 있음. 전체적으로 깨끗한 흰색과 파란색 조합이 특징임.', 1500, '음료수', '5'),
(8, '데미소다 복숭아', '복숭아 과즙이 들어있는 탄산음료. 용량 250ml의 일반 캔 형태.', '전체적으로 밝은 분홍색과 하얀색이 주를 이루는 알루미늄 캔. 캔 중앙 상단에 빨간색 굵은 글씨로 \'Demisoda\' 로고가 인쇄되어 있으며, 그 아래 \'PEACH\' 또는 \'복숭아\' 글자가 함께 표기되어 있음. 캔 하단에는 복숭아 과일 이미지 또는 분홍색 액체가 튀는 듯한 그래픽 패턴이 있음.', 1500, '음료수', '6'),
(9, '광천 파래김', '국산 원초와 들기름으로 만든 광천파래김, 조미김 4g, 20kcal, 광천농협 제조', '작은 김 비닐 포장, 9cm x 5cm x 3cm, 윗면은 검은 색에 중앙에 베이지색 카드와 \'광천 파래김\' 글씨와 작게 \'국산 들기름\' 적혀있음, 옆면과 뒷면은 밝은 베이지색에 제품정보 글씨가 자세히 적힘.', 2500, '식품', '7'),
(10, '신라면 봉지', '농심의 대표 라면. 얼큰한 맛이 특징이며, 소고기 베이스의 국물과 쫄깃한 면발.', '전체적으로 강렬한 빨간색을 띄는 직사각형 비닐 포장지. 포장지 중앙(앞)에는 흰색 테두리가 있는 검은색 글씨로 큰 한자 \'辛\'(신)이 선명하게 새겨져 있고, 조리된 라면 이미지(빨간 국물, 면, 버섯)가 크게 인쇄되어 있음. 뒷면에는 흰색 배경에 상품 정보, 영양정보 등이 표기', 1000, '식품', '8'),
(11, '스팸 클래식', '돼지고기로 만든 햄 통조림. 200g, 680kcal', '직사각형 형태의 알루미늄 금속 캔. 약 8cm x 4cm x 4cm. 옆면에는 검은 색으로 비닐이 싸여있으며, 전면에는 \'SPAM\' 글씨와 함께 스팸 사진이 작게 나와있고, 나머지 옆면에는 어두운 배경에 상품정보, 바코드 등이 표기되어 있음.', 4500, '식품', '9'),
(12, '새콤달콤 울트라레몬맛', '크라운제과의 츄잉 캔디. 기존 레몬맛보다 더 강한 신맛이 특징. 소형 포장.', '가로로 길고 얇은 직사각형 형태의 츄잉 캔디 포장지. 포장지에는 레몬 아이콘과 강한 신맛을 연상시키는 그림이 있음. 전면에는 \'쌔콤달콤\' 글씨가 노란색으로 있음', 1000, '과자', '11'),
(13, '톡핑', '헤이즐넛과 그레놀라 등 견과류가 들어가 있는 초코바. 오리온 제조, 43g, 234kcal', '전면에는 \'톡핑\' 글씨와 함께 견과류가 박힌 초콜릿 그림이 있음, 어두운 초록색 배경에 그래픽이 같이 포함된 포장지', 1200, '과자', '12');

--
-- 덤프된 테이블의 인덱스
--

--
-- 테이블의 인덱스 `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- 테이블의 인덱스 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- 덤프된 테이블의 AUTO_INCREMENT
--

--
-- 테이블의 AUTO_INCREMENT `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 테이블의 AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;
