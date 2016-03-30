<?php

require_once __DIR__ . '/functions.php';
require_unlogined_session();

// 事前に生成したユーザごとのパスワードハッシュの配列
$hashes = [
    'username' => '$2y$10$5oJchTrDqSp7L9PsZ.WxPOHw7f7YAjpiUbQvRNKMb2tNPcBfl.CMu',
];

// ユーザから受け取ったユーザ名とパスワード
$username = filter_input(INPUT_POST, 'username');
$password = filter_input(INPUT_POST, 'password');

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch (true) {
        // 妥当なCSRFトークンか
        case !validate_token(filter_input(INPUT_POST, 'token')):
        // ユーザ名が有効か
        case !isset($hashes[$username]):
        // パスワードハッシュに適合する正しいパスワードか
        case !password_verify($password, $hashes[$username]):
            // いずれか1つでも失敗したとき「403 Forbidden」にする
            http_response_code(403);
            break;
        default:
            // 全て成功したとき，セッションIDの追跡を防ぐため，セッションIDを変更する
            session_regenerate_id(true);
            // ユーザ名をセット
            $_SESSION['username'] = $username;
            // ログインが完了したので / に遷移
            header('Location: /');
            exit;
    }
}

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<title>ログインページ</title>
<h1>ログインしてください</h1>
<form method="post" action="">
    ユーザ名: <input type="text" name="username" value="">
    パスワード: <input type="password" name="password" value="">
    <input type="hidden" name="token" value="<?=h(generate_token())?>">
    <input type="submit" value="ログイン">
</form>
<?php if (http_response_code() === 403): ?>
<p style="color: red;">ユーザ名またはパスワードが違います</p>
<?php endif; ?>
