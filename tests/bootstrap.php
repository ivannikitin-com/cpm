<?php
/**
 * Bootstrap юнит-тестов: PHPUnit + WP_Mock.
 *
 * Загружает dev-зависимости Composer, затем классы плагина
 * через require() — так же, как это делают менеджеры модулей
 * (автозагрузчика в рантайме нет).
 *
 * По мере появления классов ядра они добавляются в список ниже.
 */

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

// Загрузка классов ядра. Список пополняется по мере реализации.
// Пути соответствуют docs/core/README.md.
$cpm_classes = array(
	// 'classes/plugin.php',
	// 'classes/base_manager.php',
	// 'classes/core/core_manager.php',
	// 'classes/core/entity.php',
	// 'classes/core/project.php',
	// ...
);

foreach ( $cpm_classes as $relative ) {
	$file = dirname( __DIR__ ) . '/' . $relative;
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

// Инициализация WP_Mock: перехватывает вызовы глобальных функций WordPress.
\WP_Mock::bootstrap();
