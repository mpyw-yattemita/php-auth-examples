# PHPによる簡単なログイン認証いろいろ

[こちら](http://qiita.com/mpyw/items/bb8305ba196f5105be15)にあるサンプル集です．

|ユーザ名|パスワード|
|:---:|:---:|
|`username`|`password`|

## 基本的な使い方

```ShellSession
mpyw@localhost:~$ git clone git@github.com:mpyw/php-auth-examples.git
mpyw@localhost:~$ cd php-auth-examples
mpyw@localhost:~/php-auth-examples$ basic/run.sh
```

この状態でWebブラウザから [http://localhost:8080/](http://localhost:8080/) にアクセス

## 詳細

### 認証タイプ

3つのディレクトリがあります．

|ディレクトリ名|説明|
|:---:|:---:|
|`basic`|Basic認証|
|`digest`|Digest認証|
|`session`|セッション認証|

### 動作タイプ

各ディレクトリの中にシェルスクリプトが3種類あります．

|ディレクトリ名|説明|
|:---:|:---:|
|`run.sh`|PHPのビルトインサーバを使います．|
|`run-fpm-h2o.sh`|H2Oとphp-fpmを使います．|
|`run-cgi-h2o.sh`|H2Oとphp-cgiを使います．<br />プロセス管理はH2O側で行われます．|
