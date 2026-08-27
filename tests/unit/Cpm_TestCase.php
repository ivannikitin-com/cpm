<?php
/**
 * Базовый класс юнит-тестов CPM.
 *
 * Сбрасывает синглтоны Plugin / Core_Manager и использует WP_Mock\Tools\TestCase
 * (setUp/tearDown WP_Mock, assertConditionsMet).
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Activity;
use CPM\v3\Core\Core_Manager;
use CPM\v3\Plugin;
use ReflectionClass;
use WP_Mock\Tools\TestCase as WP_Mock_TestCase;

abstract class Cpm_TestCase extends WP_Mock_TestCase {

	public function setUp(): void {
		parent::setUp();
		\WP_Mock::userFunction( 'register_post_type' );
	}

	public function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		$ref  = new ReflectionClass( Activity::class );
		$prop = $ref->getProperty( 'hooks_registered' );
		$prop->setAccessible( true );
		$prop->setValue( null, false );
		$this->reset_singleton( Plugin::class );
		$this->reset_singleton( Core_Manager::class );
		parent::tearDown();
	}

	/**
	 * Каталог плагина (source/) с завершающим слэшем.
	 *
	 * @return string
	 */
	protected function plugin_dir() {
		return dirname( __DIR__, 2 ) . '/source/';
	}

	/**
	 * Подменяет синглтон Core_Manager (в т.ч. моком без конструктора).
	 *
	 * @param Core_Manager $instance Экземпляр или мок.
	 */
	protected function set_core_manager( $instance ) {
		$ref  = new ReflectionClass( Core_Manager::class );
		$prop = $ref->getProperty( 'instance' );
		$prop->setAccessible( true );
		$prop->setValue( null, $instance );
	}

	/**
	 * Сбрасывает private static $instance у класса-синглтона.
	 *
	 * @param string $class Имя класса.
	 */
	protected function reset_singleton( $class ) {
		$ref = new ReflectionClass( $class );
		if ( ! $ref->hasProperty( 'instance' ) ) {
			return;
		}
		$prop = $ref->getProperty( 'instance' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );
	}

	/**
	 * Вызывает protected-метод объекта (для проверки get_wp_args / delete_entity).
	 *
	 * @param object $object Объект.
	 * @param string $method Имя метода.
	 * @param array  $args   Аргументы.
	 * @return mixed
	 */
	protected function invoke_protected( $object, $method, array $args = array() ) {
		$ref = new \ReflectionMethod( $object, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( $object, $args );
	}
}
