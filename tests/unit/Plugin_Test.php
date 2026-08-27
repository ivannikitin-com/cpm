<?php
/**
 * Юнит-тесты Plugin.
 *
 * Матрица tests/покрытие.md: синглтон get_instance(); init() создаёт менеджеров
 * по хуку init; log() не пишет при WP_DEBUG=false и пишет при true.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Core_Manager;
use CPM\v3\Extensions\Extensions_Manager;
use CPM\v3\Plugin;
use CPM\v3\REST\REST_API_Manager;
use CPM\v3\Settings;
use CPM\v3\UI\UI_Manager;

class Plugin_Test extends Cpm_TestCase {

	public function test_get_instance_returns_constructed_plugin() {
		$plugin = new Plugin( $this->plugin_dir(), 'http://cpm.test/' );

		$this->assertSame( $plugin, Plugin::get_instance() );
		$this->assertSame( $this->plugin_dir(), $plugin->CPM_PLUGIN_DIR );
		$this->assertSame( 'http://cpm.test/', $plugin->CPM_PLUGIN_URL );
	}

	public function test_init_creates_module_managers() {
		\WP_Mock::userFunction( 'get_option' )->andReturn( array() );
		\WP_Mock::userFunction( 'wp_parse_args' )->andReturnUsing(
			function ( $args, $defaults ) {
				return array_merge( $defaults, $args );
			}
		);

		$plugin = new Plugin( $this->plugin_dir(), 'http://cpm.test/' );
		$plugin->init();

		$this->assertInstanceOf( Core_Manager::class, $plugin->core );
		$this->assertInstanceOf( REST_API_Manager::class, $plugin->rest_api );
		$this->assertInstanceOf( UI_Manager::class, $plugin->ui );
		$this->assertInstanceOf( Extensions_Manager::class, $plugin->extensions );
		$this->assertInstanceOf( Settings::class, $plugin->settings );
		$this->assertSame( $plugin->core, Core_Manager::get_instance() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_log_does_not_write_when_wp_debug_false() {
		define( 'WP_DEBUG', false );

		$log = tempnam( sys_get_temp_dir(), 'cpm' );
		ini_set( 'error_log', $log );

		$plugin = new Plugin( $this->plugin_dir(), 'http://cpm.test/' );
		$plugin->log( 'must-not-appear', 'error' );

		$this->assertStringNotContainsString( 'must-not-appear', (string) file_get_contents( $log ) );
		unlink( $log );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_log_writes_when_wp_debug_true() {
		define( 'WP_DEBUG', true );

		$log = tempnam( sys_get_temp_dir(), 'cpm' );
		ini_set( 'error_log', $log );

		$plugin = new Plugin( $this->plugin_dir(), 'http://cpm.test/' );
		$plugin->log( 'debug-message', 'info' );

		$contents = (string) file_get_contents( $log );
		$this->assertStringContainsString( 'CPM: info: debug-message', $contents );
		unlink( $log );
	}
}
