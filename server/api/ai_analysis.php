<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// CORS 헤더 추가
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
        $stmt = $pdo->prepare("SELECT id, name, price, position FROM products WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (Exception $e) {
        return null;
    }
}

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


function callGeminiAPI($imageData, $mimeType, $prompt) {
    global $GOOGLE_API_KEY;
    
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $GOOGLE_API_KEY;
    
    $data = [
        'contents' => [
            [
                'role' => 'user',
                'parts' => [
                    [
                        'text' => $prompt
                    ],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => base64_encode($imageData)
                        ]
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.3,
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

function processImageAnalysis() {
    global $GOOGLE_API_KEY;
    
    try {        
        // 0. DB에서 상품 목록 가져오기
        $products = getProductsFromDB();
        $prompt = buildPrompt($products);
        
        // 1. Motion에 사진 찍기 요청
        // 사진 찍기 요청 -> curl 지원 안돼서 그냥 exec 쓰기
        exec('curl http://127.0.0.1:8080/0/action/snapshot');
        usleep(200000); // 0.2초 대기
        exec('curl http://127.0.0.1:8080/0/action/snapshot');
        
        // 2. Motion 디렉토리에서 가장 최근 snapshot 찾기
        $motionDir = '/var/lib/motion';
        $snapshotFiles = glob($motionDir . '/*-snapshot.jpg');
        
        if (empty($snapshotFiles)) {
            throw new Exception('No snapshot files found');
        }
        
        // 수정 시간 기준으로 정렬 (최신순)
        usort($snapshotFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
	    
        // 가장 최근 파일 선택
        $imagePath = $snapshotFiles[0];
        
        // 오래된 snapshot 파일 정리 (최근 10개만 유지)
        if (count($snapshotFiles) > 10) {
            for ($i = 10; $i < count($snapshotFiles); $i++) {
                @unlink($snapshotFiles[$i]);
            }
        }
        
        // 3. 이미지 데이터 읽기
        $imageData = file_get_contents($imagePath);
        if ($imageData === false) {
            throw new Exception('Failed to read image file');
        }

        $mimeType = 'image/jpeg'; // Motion 스냅샷은 항상 jpg
        
        // 4. Gemini API 호출 (프롬프트 전달)
        $response = callGeminiAPI($imageData, $mimeType, $prompt);
        
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
                        'location' => '',
                        'analysis' => $parsedResult['analysis'] ?? ''
                    ]);
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
    // 응답 즉시 반환 (클라이언트가 기다리지 않게 하려면 필요하지만, 
    // 여기서는 결과를 기다려야 하므로 즉시 반환 부분은 제거하고 결과만 출력합니다)
    // 만약 비동기로 처리하려면 별도 로직이 필요하지만, 현재 구조상 동기 처리가 적합해 보입니다.
    
    // 분석 실행 및 결과 출력
    echo processImageAnalysis();
    
} catch (Exception $e) {
    echo json_encode(['success' => -1, 'error' => $e->getMessage()]);
}
?>