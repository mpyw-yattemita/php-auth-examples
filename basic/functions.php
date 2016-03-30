<?php

/**
 * Basic認証を要求するページの先頭で使う関数
 * 初回時または失敗時にはヘッダを送信してexitする
 *
 * @return string ログインしたユーザ名
 */
function require_basic_auth()
{
    // 事前に生成したユーザごとのパスワードハッシュの配列
    $hashes = [
        'username' => '$2y$10$5oJchTrDqSp7L9PsZ.WxPOHw7f7YAjpiUbQvRNKMb2tNPcBfl.CMu',
    ];

    switch (true) {
        // Basic認証に必要なパラメータが送信されたかどうか
        case !isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']):
        // ユーザ名が有効か
        case !isset($hashes[$_SERVER['PHP_AUTH_USER']]):
        // パスワードハッシュに適合する正しいパスワードかどうか
        case !password_verify($_SERVER['PHP_AUTH_PW'], $hashes[$_SERVER['PHP_AUTH_USER']]):
            // いずれか1つでも失敗したとき
            header('WWW-Authenticate: Basic realm="Enter username and password."');
            header('Content-Type: text/plain; charset=utf-8');
            exit('このページを見るにはログインが必要です');
        default:
            // 全て成功したときにはユーザ名を返す
            return $_SERVER['PHP_AUTH_USER'];
    }
}

/**
 * htmlspecialcharsのラッパー関数
 *
 * @param string $str
 * @return string
 */
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
