<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Plaintext로 출력
header('Content-Type: text/plain; charset=utf-8');

// DB 연결
require_once __DIR__ . '/../manager/db.php';

// DB에서 상품 목록 가져오기
function getProductsFromDB() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT id, name, price, category, position, description, vision_desc FROM products ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// 프롬프트 생성
function buildPrompt($products) {
    $productList = "ID|name|price(KRWON)|category|description|vision_desc\n";
    foreach ($products as $product) {        
        $line = "{$product['id']}|{$product['name']}|{$product['price']}|{$product['category']}|{$product['description']}|{$product['vision_desc']}\n";
        $productList .= str_replace("\r\n", ",", $line);
    }
    
    $prompt = "당신은 시각 장애인을 위한 상품 설명 에이전트 AI입니다. 제공된 이미지는 고객이 키오스크 카메라로 촬영한 상품 사진입니다.

## 판매 중인 상품 목록 (DB):
{$productList}

## 지시사항:
1. 이미지의 상품을 식별하기 위해 다음 순서대로 진행하세요:
   - 패키지 색상 (색상, 패턴, 디자인)
   - 패키지 모양과 크기
   - 브랜드 로고 및 텍스트
   - 전체 시각적 특징

2. 각 상품의 vision_desc와 이미지를 비교하여 매핑하세요 (가장 중요한 단계):
   - 예: \"빨간색 라벨에 어두운 갈색의 액체 담긴 음료수 병\" → 코카 콜라, 추가적으로 라벨의 텍스트를 확인하여 제로인지 일반인지 구분

3. 매핑되는 상품이 있으면:
   - 해당 상품의 id를 파악
   - vision_desc를 참고하여 상품의 시각적 특징을 강조한 TTS용 설명 작성
   - 상품 이름, 가격, 주요 특징 포함

4. 매핑되는 상품이 없으면:
   - id를 -1로 설정
   - analysis에 \"죄송합니다. 이 상품은 현재 판매 중인 상품이 아닙니다.\"라고 작성

## 출력 형식 (반드시 이 JSON 형식만 출력):
{
  \"id\": 상품ID (정수, 없으면 -1),
  \"analysis\": \"TTS용 상품 설명 (plaintext, 이모지/마크다운 없이)\"
}

JSON만 출력하고 다른 텍스트는 포함하지 마세요.";
    
    return $prompt;
}

// 상품 목록 가져오기 및 프롬프트 생성
$products = getProductsFromDB();
$prompt = buildPrompt($products);

// Plaintext로 출력
echo $prompt;
?>
