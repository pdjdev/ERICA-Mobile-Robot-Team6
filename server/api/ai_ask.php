<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// CORS 헤더 추가
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// OPTIONS 요청 처리 (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// DB 연결
require_once __DIR__ . '/../manager/db.php';

// API 설정 불러오기
require_once __DIR__ . '/settings.php';

// Gemini API 설정은 settings.php에서 불러옴

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

// ID로 상품 정보 조회
function getProductByID($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, name, price, category, position FROM products WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function buildPrompt($products, $userQuery) {
    $productList = "ID|name|price(KRWON)|category|position|description\n";
    foreach ($products as $product) {        
        $line = "{$product['id']}|{$product['name']}|{$product['price']}|{$product['category']}|{$product['position']}|{$product['description']}\n";
        $productList .= str_replace("\r\n", ",", $line);
    }
    
    $prompt = "당신은 시각 장애인을 위한 상품 추천 및 위치 안내 AI입니다. 사용자가 음성으로 입력한 상품 검색 쿼리를 받아서, 매장의 상품 데이터베이스에서 해당 상품을 찾아주는 역할을 합니다.

## 판매 중인 상품 목록 (DB):
{$productList}

## 사용자 검색 쿼리:
\"{$userQuery}\"

## 지시사항:
1. 사용자의 검색 쿼리를 분석하여 찾고 있는 상품을 식별하세요
   - 예: \"콜라 어딨어?\", \"펩시 있어?\", \"초콜릿 주세요\" 등 다양한 형태의 질문을 이해해야 합니다
   - 사용자가 상품 이름, 카테고리, 또는 설명을 통해 상품을 검색할 수 있습니다

2. DB의 상품 목록에서 사용자의 의도와 가장 일치하는 상품을 찾으세요:
   - 제품명, 카테고리, 설명을 종합적으로 판단
   - 가장 적합한 상품 ID를 선택

3. 일치하는 상품을 찾으면:
   - 상품의 id를 파악
   - 상품 이름, 가격, 위치를 포함한 친절한 TTS용 안내 메시지 작성
   - 존댓말로 자연스럽고 정중하게 작성
   - 비전 설명은 포함 금지! 오직 상품의 실질적인 설명만 (가격, 칼로리, 맛, 타입, 행사여부 등)

4. 일치하는 상품이 없으면:
   - id를 -1로 설정
   - analysis에 \"죄송합니다. 찾으시는 상품은 현재 매장에서 판매 중이 아닙니다.\"라고 작성

## 출력 형식 (반드시 이 JSON 형식만 출력):
{
  \"id\": 상품ID (정수, 없으면 -1),
  \"analysis\": \"TTS용 안내 메시지 (plaintext, 이모지/마크다운 없이, 존댓말)\"
}

JSON만 출력하고 다른 텍스트는 포함하지 마세요.";
    
    return $prompt;
}

function callGeminiAPI($prompt) {
    global $GOOGLE_API_KEY;
    
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent?key=' . $GOOGLE_API_KEY;
    
    $data = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    [
                        'text' => $prompt
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.2,
            'responseMimeType' => 'application/json',
            'responseSchema' => [
                'type' => 'object',
                'properties' => [
                    'id' => [
                        'type' => 'integer'
                    ],
                    'analysis' => [
                        'type' => 'string'
                    ]
                ],
                'required' => [
                    'id',
                    'analysis'
                ]
            ]
        ]
    ];
    
    $options = [
        'http' => [
            'header' => "Content-Type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        throw new Exception('Failed to call Gemini API');
    }
    
    return json_decode($result, true);
}

function processQuery($userQuery) {
    global $GOOGLE_API_KEY;
    
    try {
        // 1. 입력값 검증
        if (empty($userQuery)) {
            return json_encode([
                'success' => -1,
                'error' => 'Query parameter is required'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        // 2. DB에서 상품 목록 가져오기
        $products = getProductsFromDB();
        
        if (empty($products)) {
            return json_encode([
                'success' => -1,
                'error' => 'No products found in database'
            ], JSON_UNESCAPED_UNICODE);
        }
        
        // 3. 프롬프트 구성
        $prompt = buildPrompt($products, $userQuery);
        
        // 4. Gemini API 호출 (프롬프트만 전달)
        $response = callGeminiAPI($prompt);
        
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $resultText = $response['candidates'][0]['content']['parts'][0]['text'];
            
            // JSON 파싱
            $parsedResult = json_decode($resultText, true);
            
            if ($parsedResult !== null && isset($parsedResult['id'])) {
                $productId = (int)$parsedResult['id'];
                
                if ($productId === -1) {
                    // 상품 없음
                    return json_encode([
                        'success' => 1,
                        'id' => -1,
                        'name' => '',
                        'price' => -1,
                        'category' => '',
                        'location' => '',
                        'analysis' => $parsedResult['analysis'] ?? '죄송합니다. 찾으시는 상품은 현재 매장에서 판매 중이 아닙니다.'
                    ], JSON_UNESCAPED_UNICODE);
                }
                
                // ID로 DB 조회
                $product = getProductByID($productId);
                
                if ($product) {
                    // 상품 조회 성공
                    return json_encode([
                        'success' => 1,
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => (int)$product['price'],
                        'category' => $product['category'] ?? '',
                        'location' => $product['position'] ?? '',
                        'analysis' => $parsedResult['analysis'] ?? ''
                    ], JSON_UNESCAPED_UNICODE);
                } else {
                    // ID가 있지만 DB에 없음
                    return json_encode([
                        'success' => -1,
                        'error' => 'Product ID not found in database',
                        'id' => $productId
                    ], JSON_UNESCAPED_UNICODE);
                }
            } else {
                // JSON 파싱 실패
                return json_encode([
                    'success' => -1,
                    'error' => 'Invalid JSON response from AI',
                    'raw_response' => $resultText
                ], JSON_UNESCAPED_UNICODE);
            }
        } else {
            throw new Exception('Invalid API response');
        }
        
    } catch (Exception $e) {
        // 에러 리턴
        return json_encode(['success' => -1, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

try {
    // GET 파라미터에서 query 값 추출
    $userQuery = isset($_GET['query']) ? trim($_GET['query']) : '';
    
    // 쿼리 처리 및 결과 출력
    echo processQuery($userQuery);
    
} catch (Exception $e) {
    echo json_encode(['success' => -1, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
