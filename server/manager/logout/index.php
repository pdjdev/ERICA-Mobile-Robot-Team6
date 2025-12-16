<?php
session_start();
// 로그아웃 처리 후 로그인 페이지로 이동합니다.
session_destroy();
header("Location: ../login/index.php");
exit;
