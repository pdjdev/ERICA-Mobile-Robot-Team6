<?php
session_start();
// 로그인되어 있으면 상품 관리 페이지로, 아니면 로그인 페이지로 이동합니다.
if (isset($_SESSION['user_id'])) {
    header("Location: items/index.php");
} else {
    header("Location: login/index.php");
}
exit;
