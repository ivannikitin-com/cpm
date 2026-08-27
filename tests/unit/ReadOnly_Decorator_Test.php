<?php
/**
 * Юнит-тесты ReadOnly_Decorator.
 *
 * Матрица tests/покрытие.md: can_modify() = false; save()/delete() не изменяют БД.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\AccessDeniedException;
use CPM\v3\Core\Demo_Entity;
use CPM\v3\Core\ReadOnly_Decorator;

class ReadOnly_Decorator_Test extends Cpm_TestCase {

	public function test_can_modify_is_false() {
		$dec = new ReadOnly_Decorator( new Demo_Entity() );
		$this->assertFalse( $this->invoke_protected( $dec, 'can_modify' ) );
	}

	public function test_save_does_not_call_wp_update_post() {
		\WP_Mock::userFunction( 'wp_update_post' )->never();
		\WP_Mock::userFunction( 'wp_insert_post' )->never();

		$dec = new ReadOnly_Decorator( new Demo_Entity( array( 'id' => 4, 'title' => 'X' ) ) );

		$this->expectException( AccessDeniedException::class );
		$dec->save();
	}
}
