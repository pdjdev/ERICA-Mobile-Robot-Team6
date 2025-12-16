<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);



/*
motion에서 사진을 찍고 해당 사진을 리턴해주는 스크립트
1. 사진은 curl http://127.0.0.1:8080/0/action/snapshot 로 요청하면 됨
2. curl에서 200 응답이 오면 사진이 찍힌 것임
3. 찍힌 사진은 /var/lib/motion/lastsnap.jpg 에 저장됨
4. 해당 사진을 php로 읽고 blob/jpg로 리턴
*/

try {
    // 사진 찍기 요청 -> curl 지원 안돼서 그냥 exec 쓰기
    exec('curl http://127.0.0.1:8080/0/action/snapshot');
    usleep(200000); // 0.2초 대기
    exec('curl http://127.0.0.1:8080/0/action/snapshot');
    
    // motion 디렉토리에서 가장 최근 snapshot 찾기
    $motionDir = '/var/lib/motion';
    $snapshotFiles = glob($motionDir . '/*-snapshot.jpg');
    
    if (empty($snapshotFiles)) {
        throw new Exception('No snapshot files found');
    }
    
    // 수정 시간 기준으로 정렬 (최신순)
    usort($snapshotFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // 가장 최근 파일
    $latestSnapshot = $snapshotFiles[0];
    
    // 오래된 snapshot 파일 정리 (최근 10개만 유지)
    if (count($snapshotFiles) > 10) {
        for ($i = 10; $i < count($snapshotFiles); $i++) {
            @unlink($snapshotFiles[$i]);
        }
    }

    // 사진 읽기
    $imageData = file_get_contents($latestSnapshot);
    if ($imageData === false) {
        throw new Exception('Failed to read image file');
    }

    // 사진 출력
    header('Content-Type: image/jpeg');
    echo $imageData;

} catch (Exception $e) {
    // 에러 처리
    header('Content-Type: application/json');
    echo json_encode(['success' => -1, 'error' => $e->getMessage()]);
}
