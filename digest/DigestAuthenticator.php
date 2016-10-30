<?php

/**
 * 【完全版: 盗聴されてもなりすましは防ぐことができます】
 */
class DigestAuthenticator
{
    /**
     * 定数各種設定
     */
    const DB_FILENAME = 'digest.db'; // テンポラリディレクトリに作るSQLite3のデータベースファイル名
    const AUTH_REALM = 'Enter username and password.'; // Realm
    const AUTH_MESSAGE = 'このページを見るにはログインが必要です'; // 失敗時に出力するテキスト
    const DATE_TIMEZONE = 'Asia/Tokyo'; // タイムゾーン
    const DATE_FORMAT = 'Y-m-d H:i:s'; // 日付形式
    const DATE_EXPIRY = 14400; // nonceの有効期限秒数
    const GC_PROBABILITY = 0.05; // GCを実行する確率

    /**
     * 許可するユーザのMD5ハッシュ一覧
     *
     * @var array
     */
    private static $digests = [
        'username' => '0001656c35eb6e548b9e2afcae21f7e9',
    ];

    /**
     * PDOのインスタンス
     *
     * @var PDO
     */
    private $pdo;

    /**
     * ダイジェストの検証を行い，成功時にはユーザ名を返し，失敗時は強制終了する
     *
     * @return string ユーザ名
     */
    public static function verify()
    {
        $self = new self;
        if (
            !isset($_SERVER['PHP_AUTH_DIGEST']) ||
            !is_string($username = $self->verifyActual($_SERVER['PHP_AUTH_DIGEST']))
        ) {
            // 初回時または失敗時には新しくnonceを生成して強制終了
            $nonce = $self->createSession();
            header('WWW-Authenticate: Digest realm="' . self::AUTH_REALM . '", qop=auth, nonce="' . $nonce . '"');
            header('Content-Type: text/plain; charset=utf-8');
            exit(self::AUTH_MESSAGE);
        }
        // 成功時にはユーザ名を返す
        return $username;
    }

    /**
     * インスタンスの初期化
     * DBへの接続およびGCはここで行う
     */
    private function __construct()
    {
        $this->pdo = new \PDO('sqlite:' . sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::DB_FILENAME, '', '');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // テーブルの作成
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS sessions(
            nonce TEXT PRIMARY KEY,
            nc TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )");
        // 指定した確率でGCを実行
        if (mt_rand() / mt_getrandmax() <= self::GC_PROBABILITY) {
            $past = new \DateTime('-' . self::DATE_EXPIRY . ' sec ' . self::DATE_TIMEZONE);
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE updated_at < ?");
            $stmt->execute([$past->format(self::DATE_FORMAT)]);
        }
    }

    /**
     * 必ずverifyResponse()とverifyAndUpdateSession()の両方を実行する
     *
     * @param string $header PHP_AUTH_DIGESTの値
     * @return string ユーザ名
     */
    private function verifyActual($header)
    {
        $params = self::parse($header);
        $a = $this->verifyResponse($params);
        $b = $this->verifyAndUpdateSession($params);
        return $a && $b ? $params['username'] : false;
    }

    /**
     * nonceおよびncの妥当性検証，ncの更新
     *
     * @param array $params パースされたPHP_AUTH_DIGEST
     * @return bool 妥当性
     */
    private function verifyAndUpdateSession(array $params)
    {
        // 現在日付時刻
        $now = new \DateTime('now ' . self::DATE_TIMEZONE);
        // 現在からEXPIRY秒前の日付時刻
        // これより現在日付時刻のほうが大きければ期限内
        $past = new \DateTime('-' . self::DATE_EXPIRY . ' sec ' . self::DATE_TIMEZONE);
        // nonce
        $nonce = $params['nonce'];
        // nc (16進数表記8桁)
        $nc = $params['nc'];
        // ncに1足した値 (16進数表記8桁)
        $next_nc = base_convert($nc, 16, 10);
        $next_nc = bcadd($next_nc, '1');
        $next_nc = base_convert($next_nc, 10, 16);
        $next_nc = str_pad($next_nc, 8, '0', STR_PAD_LEFT);
        // UPDATEを実行
        $stmt = $this->pdo->prepare("
            UPDATE sessions
            SET
                nc = ?,
                updated_at = ?
            WHERE
                nonce = ? AND nc = ? AND updated_at > ?
        ");
        $stmt->execute([
            $next_nc,
            $now->format(self::DATE_FORMAT),
            $nonce,
            $nc,
            $past->format(self::DATE_FORMAT),
        ]);
        // 更新された行があれば成功
        return (bool)$stmt->rowCount();
    }

    /**
     * responseの妥当性検証
     *
     * @param array $params パースされたPHP_AUTH_DIGEST
     * @return bool 妥当性
     */
    private function verifyResponse(array $params)
    {
        // Digest認証の形式に従ってresponseを検証
        $expected = md5(implode(':', [
            isset(self::$digests[$params['username']]) ? self::$digests[$params['username']] : '',
            $params['nonce'],
            $params['nc'],
            $params['cnonce'],
            $params['qop'],
            md5("$_SERVER[REQUEST_METHOD]:$params[uri]")
        ]));
        // 比較はhash_equals関数を使って固定時間で行う
        return hash_equals($expected, $params['response']);
    }

    /**
     * セッションの初期化
     *
     * @return string 新たに生成したnonce
     */
    private function createSession()
    {
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO sessions VALUES (?, ?, ?)");
        do {
            // 衝突が起こらないようになるまでnonce生成を繰り返す
            $nonce = md5(openssl_random_pseudo_bytes(30));
            $date = new \DateTime('now ' . self::DATE_TIMEZONE);
            $stmt->execute([$nonce, '00000001', $date->format(self::DATE_FORMAT)]);
        } while (!$stmt->rowCount());
        return $nonce;
    }

    /**
     * Authorizationヘッダのパースを行う
     *
     * @param  string $header Authorizationヘッダ
     * @return array          パースして得られた連想配列
     */
    private static function parse($header)
    {
        $keys = ['response', 'nonce', 'nc', 'cnonce', 'qop', 'uri', 'username'];
        $p = array_fill_keys($keys, '');
        $regex = '/(' . implode('|', $keys) . ')=(?:\'([^\']++)\'|"([^"]++)"|([^\s,]++))/';
        preg_match_all($regex, $header, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $p[$m[1]] = $m[3] ?: $m[4];
        }
        return $p;
    }
}
