<?php

/**
 * Digest認証を要求するページの先頭で使う関数
 * 初回時または失敗時にはヘッダを送信してexitする
 *
 * @return string ログインしたユーザ名
 */
function require_digest_auth()
{
    // 事前に生成したユーザごとのダイジェストの配列
    $digests = [
        'username' => '0001656c35eb6e548b9e2afcae21f7e9',
    ];

    // Authorizationヘッダの認証を行うクロージャ
    $verify = function ($header) use ($digests) {
        // 利用するパラメータ
        $keys = ['response', 'nonce', 'nc', 'cnonce', 'qop', 'uri', 'username'];
        // あらかじめ空欄で埋めておく
        $p = array_fill_keys($keys, '');
        // 正規表現を生成してパラメータをパース
        $regex = '/(' . implode('|', $keys) . ')=(?:\'([^\']++)\'|"([^"]++)"|([^\s,]++))/';
        preg_match_all($regex, $header, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            // 見つかったところは空欄を上書き
            $p[$m[1]] = $m[3] ?: $m[4];
        }
        // ユーザ名に対応するダイジェストを取り出し，期待されるレスポンスを生成する
        $expected = md5(implode(':', [
            isset($digests[$p['username']]) ? $digests[$p['username']] : md5(''),
            $p['nonce'],
            $p['nc'],
            $p['cnonce'],
            $p['qop'],
            md5("$_SERVER[REQUEST_METHOD]:$p[uri]")
        ]));
        // 検証結果が正しければユーザ名を返す
        return hash_equals($expected, $p['response']) ? $p['username'] : false;
    };

    if (
        !isset($_SERVER['PHP_AUTH_DIGEST']) ||
        !is_string($username = $verify($_SERVER['PHP_AUTH_DIGEST']))
    ) {
        // 初回時または認証が失敗したとき
        $nonce = md5(openssl_random_pseudo_bytes(30));
        header('WWW-Authenticate: Digest realm="Enter username and password.", qop=auth, nonce="' . $nonce . '"');
        header('Content-Type: text/plain; charset=utf-8');
        exit('このページを見るにはログインが必要です');
    }

    // 認証が成功したときはユーザ名を返す
    return $username;
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
