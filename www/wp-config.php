<?php
/**
 * wp-config.php — шаблон для стенда Docker (cpm-dev).
 *
 * БД доступна внутри сети Docker по имени сервиса `mysql` (порт 3306).
 * На хосте (например, для импорта данных) — по адресу localhost:3306.
 * Дополните соли и при необходимости правки.
 */

// ===== MySQL (сервис `mysql` из docker-compose) =====
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', 'wordpress' );
define( 'DB_PASSWORD', 'wordpress' );
define( 'DB_HOST', 'mysql' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// ===== Секретные ключи =====
// Замените пустые строки: https://api.wordpress.org/secret-key/1.1/salt/
define( 'AUTH_KEY',         'put_your_unique_phrase_here' );
define( 'SECURE_AUTH_KEY',  'put_your_unique_phrase_here' );
define( 'LOGGED_IN_KEY',    'put_your_unique_phrase_here' );
define( 'NONCE_KEY',        'put_your_unique_phrase_here' );
define( 'AUTH_SALT',        'put_your_unique_phrase_here' );
define( 'SECURE_AUTH_SALT', 'put_your_unique_phrase_here' );
define( 'LOGGED_IN_SALT',   'put_your_unique_phrase_here' );
define( 'NONCE_SALT',       'put_your_unique_phrase_here' );

$table_prefix = 'wp_';

// ===== Отладка =====
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );  // пишет в wp-content/debug.log (смонтирован в log/wp-content/debug.log)
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );

// ===== Почта (MailHog: http://localhost:8025, SMTP localhost:1025) =====
// Чтобы WordPress отправлял почту в MailHog, используйте mu-plugin
// с настройкой PHPMailer (SMTP host `mailhog`, порт 1025), либо WP Mail SMTP.

// Отключаем автообновления (стенд).
define( 'WP_AUTO_UPDATE_CORE', false );
define( 'DISALLOW_FILE_EDIT', false );

// ===== Настройки URL (заполните при необходимости) =====
// define( 'WP_HOME', 'http://localhost:8081' );
// define( 'WP_SITEURL', 'http://localhost:8081' );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
