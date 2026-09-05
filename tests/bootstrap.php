<?php
/**
 * Bootstrap юнит-тестов: PHPUnit + WP_Mock.
 *
 * Загружает dev-зависимости Composer, затем классы плагина
 * через require() — так же, как это делают менеджеры модулей
 * (автозагрузчика в рантайме нет).
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, 10up/wp_mock 1.1.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}

// Dev-зависимости Composer (PHPUnit, WP_Mock, Mockery).
$autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( ! file_exists( $autoload ) ) {
	fwrite( STDERR, "Не установлены dev-зависимости Composer. Выполните: composer install\n" );
	exit( 1 );
}
require $autoload;

// Константы плагина, как в cpm.php (см. docs/05-константы.md).
if ( ! defined( 'CPM_FORCE_DELETE' ) ) {
	define( 'CPM_FORCE_DELETE', true );
}
if ( ! defined( 'CPM_BACKWARD_COMPATIBILITY' ) ) {
	define( 'CPM_BACKWARD_COMPATIBILITY', true );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

// Минимальный стаб WP_Error для юнит-тестов (без ядра WordPress).
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $errors = array();

		public function __construct( $code = '', $message = '' ) {
			if ( '' !== $code ) {
				$this->errors[ $code ][] = $message;
			}
		}

		public function get_error_code() {
			if ( ! $this->errors ) {
				return '';
			}
			return key( $this->errors );
		}

		public function get_error_message() {
			$code = $this->get_error_code();
			if ( '' === $code || empty( $this->errors[ $code ] ) ) {
				return '';
			}
			return $this->errors[ $code ][0];
		}

		public function add( $code, $message ) {
			$this->errors[ $code ][] = $message;
		}
	}
}

// Загрузка классов плагина. Список пополняется по мере реализации.
// Порядок повторяет загрузку в source/cpm.php и конструкторах менеджеров.
$cpm_classes = array(
	'source/classes/plugin.php',
	'source/classes/base_manager.php',
	'source/classes/settings.php',
	'source/classes/core/cpm_exception.php',
	'source/classes/core/bad_user_exception.php',
	'source/classes/core/access_denied_exception.php',
	'source/classes/core/entity_save_exception.php',
	'source/classes/core/acl.php',
	'source/classes/core/user.php',
	'source/classes/core/team.php',
	'source/classes/core/entity.php',
	'source/classes/core/project_entity.php',
	'source/classes/core/project.php',
	'source/classes/core/task_list.php',
	'source/classes/core/task.php',
	'source/classes/core/message.php',
	'source/classes/core/milestone.php',
	'source/classes/core/note.php',
	'source/classes/core/comment.php',
	'source/classes/core/attachment.php',
	'source/classes/core/activity.php',
	'source/classes/core/base_decorator.php',
	'source/classes/core/read_only_decorator.php',
	'source/classes/core/modify_decorator.php',
	'source/classes/core/modify_own_decorator.php',
	'source/classes/core/stuff_decorator.php',
	'source/classes/core/core_manager.php',
	'source/classes/rest_api/rest_api_manager.php',
	'source/classes/ui/ui_manager.php',
	'source/extensions/extensions_manager.php',
	'tests/unit/Cpm_TestCase.php',
	'tests/unit/stubs/Demo_Entity.php',
	'tests/unit/stubs/Demo_Project_Entity.php',
	'tests/unit/stubs/Typed_Entity.php',
);

foreach ( $cpm_classes as $relative ) {
	$file = dirname( __DIR__ ) . '/' . $relative;
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

// Инициализация WP_Mock: перехватывает вызовы глобальных функций WordPress.
\WP_Mock::bootstrap();
