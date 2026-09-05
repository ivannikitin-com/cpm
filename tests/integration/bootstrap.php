<?php
/**
 * Bootstrap интеграционных тестов: стандартный каркас WordPress (WP test suite).
 *
 * Используется пакет wp-phpunit/wp-phpunit (см. tests/конфигурация.md).
 * Требует переменную окружения WP_TESTS_DIR и тестовую БД cpm_test.
 *
 * ВАЖНО: интеграционные тесты выполняются ТОЛЬКО против тестовой БД,
 * никогда против рабочей БД стенда.
 */

$wp_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $wp_tests_dir ) {
	fwrite(
		STDERR,
		"Не задана WP_TESTS_DIR. Пример: vendor/wp-phpunit/wp-phpunit (см. tests/конфигурация.md)\n"
	);
	exit( 1 );
}

// Каркас wp-phpunit берёт конфиг из env WP_PHPUNIT__TESTS_CONFIG
// (см. vendor/wp-phpunit/wp-phpunit/wp-tests-config.php).
$tests_config = getenv( 'WP_PHPUNIT__TESTS_CONFIG' );
if ( ! $tests_config ) {
	$tests_config = __DIR__ . '/../wp-tests-config.php';
	if ( file_exists( $tests_config ) ) {
		putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . $tests_config );
	} else {
		fwrite(
			STDERR,
			"Не найден wp-tests-config.php. Задайте WP_PHPUNIT__TESTS_CONFIG или создайте tests/wp-tests-config.php\n"
		);
		exit( 1 );
	}
}

// Загрузка функций и bootstrap тест-каркаса WordPress.
require_once $wp_tests_dir . '/includes/functions.php';

// Подключение плагина при загрузке тестовой среды (аналог mu-plugin).
tests_add_filter(
	'muplugins_loaded',
	function () {
		$main_file = dirname( __DIR__, 2 ) . '/source/cpm.php';
		if ( file_exists( $main_file ) ) {
			require_once $main_file;
		}
	}
);

require_once $wp_tests_dir . '/includes/bootstrap.php';
