<?php

//*
require_once __DIR__ . '/DigestAuthenticator.php';
$username = DigestAuthenticator::verify();
/*/
require_once __DIR__ . '/functions.php';
$username = require_digest_auth();
//*/

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<title>会員限定ページ</title>
<h1>ようこそ,<?=htmlspecialchars($username, ENT_QUOTES, 'UTF-8')?>さん</h1>
<a href="http://dummy@localhost:8080/">ログアウト</a>
