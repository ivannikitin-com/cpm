<?php
/**
 * Plugin Name: CPM v3
 * Plugin URI:  https://ivannikitin.com
 * Description: Система управления задачами в проектах клиентов.
 * Version:     3.0.0
 * Author:      Ivan Nikitin
 * Text Domain: cpm
 *
 * @package CPM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Режимы работы плагина (см. docs/05-константы.md).
if ( ! defined( 'CPM_FORCE_DELETE' ) ) {
	define( 'CPM_FORCE_DELETE', true );
}

if ( ! defined( 'CPM_BACKWARD_COMPATIBILITY' ) ) {
	define( 'CPM_BACKWARD_COMPATIBILITY', true );
}

// Загрузка классов менеджеров модулей. Автозагрузчик не используется
// (см. docs/03-base-manager.md). Классы своих модулей загружают сами менеджеры.
require_once __DIR__ . '/classes/plugin.php';
require_once __DIR__ . '/classes/base_manager.php';
require_once __DIR__ . '/classes/settings.php';
require_once __DIR__ . '/classes/core/core_manager.php';
require_once __DIR__ . '/classes/rest_api/rest_api_manager.php';
require_once __DIR__ . '/classes/ui/ui_manager.php';
require_once __DIR__ . '/extensions/extensions_manager.php';

// Инициализация плагина (см. docs/02-plugin.md).
new \CPM\v3\Plugin(
	plugin_dir_path( __FILE__ ),
	plugin_dir_url( __FILE__ )
);
