<?php
/**
 * wp-config.php — шаблон для стенда Docker (cpm-dev).
 *
 * БД доступна внутри сети Docker по имени сервиса `mysql` (порт 3306).
 * На хосте (например, для импорта данных) — по адресу localhost:3306.
 * Дополните соли и при необходимости правки.
 */

// ===== MySQL (из env контейнера PHP: DB_* ← docker-compose / .env) =====
define( 'DB_NAME', getenv( 'DB_NAME' ) ?: 'CPM' );
define( 'DB_USER', getenv( 'DB_USER' ) ?: 'cpm' );
define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) ?: '' );
define( 'DB_HOST', getenv( 'DB_HOST' ) ?: 'mysql' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$table_prefix = 'wp_';

// ===== Секретные ключи =====
// Замените пустые строки: https://api.wordpress.org/secret-key/1.1/salt/
define('AUTH_KEY',         'zY2Y81Q>-tUXO-|=jhThUH3RT)a{F+f4xn{0vRGxH|=#r}a3HUld8mPGUag-ZlE^');
define('SECURE_AUTH_KEY',  'tUtkk5#?0vajm/%8.E_UyL]l4Wira*IN-+CGYx}l]=rpGYxYw}+nLT+%sM_7GfG.');
define('LOGGED_IN_KEY',    '{nxfTj0~tfJ:{Y4T2n%5:G+o0^}%Y1&u;a^nYqfAC e)0x)gfx83o&tL3psvAD?}');
define('NONCE_KEY',        ']Z1xEo-G^B3/wtM1^$9H6Lgot*DY0KAwo3.:huZqsq?]+e~A<D@b[>f7c+dLkMG5');
define('AUTH_SALT',        'k1EtDvd*  6v+|+vncN%9lvM}|lm^kg]Gj~y|G(;^Jq;&``GoT-QtT6,|Gg>?s3a');
define('SECURE_AUTH_SALT', 'X_w3CYR->h2EhR+yME(?3|UF?NaS-->^g-L9`Ls59+=8u?]!}#_Brm!@i+pRq?EF');
define('LOGGED_IN_SALT',   'wto/tG@&| -77]B]TQs1= rH/;E&y9n& G=R8Ql 6j7`|pw(3VP&bJg-D {>+HtT');
define('NONCE_SALT',       '9GQMxoa$V_Fc/0?>~ot5?#|=bo4gbR5F_zXz+{$;uyWr(/6s6R<4ix(a=e8<u)]s');

// ===== Отладка =====
define( 'WP_DEBUG', true );
// Пишет в /var/log/php/wp-debug.log → на хосте log/php74|php84/wp-debug.log
define( 'WP_DEBUG_LOG', '/var/log/php/wp-debug.log' );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );

// ===== Почта (MailHog: http://localhost:8025, SMTP localhost:1025) =====
// Чтобы WordPress отправлял почту в MailHog, используйте mu-plugin
// с настройкой PHPMailer (SMTP host `mailhog`, порт 1025), либо WP Mail SMTP.

// Отключаем автообновления (стенд).
define( 'WP_AUTO_UPDATE_CORE', false );
define( 'DISALLOW_FILE_EDIT', false );

// ===== URL (основной стенд v3 — порт 8081; переопределение через env) =====
define( 'WP_HOME', getenv( 'WP_HOME' ) ?: 'http://localhost:8081' );
define( 'WP_SITEURL', getenv( 'WP_SITEURL' ) ?: 'http://localhost:8081' );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
