<?php

require_once __DIR__ . '/functions.php';
require_logined_session();

// CSRFトークンを検証
if (!validate_token(filter_input(INPUT_GET, 'token'))) {
    // 「400 Bad Request」
    header('Content-Type: text/plain; charset=UTF-8', true, 400);
    exit('トークンが無効です');
}

// セッション用Cookieの破棄
setcookie(session_name(), '', 1);
// セッションファイルの破棄
session_destroy();
// ログアウト完了後に /login.php に遷移
header('Location: /login.php');
