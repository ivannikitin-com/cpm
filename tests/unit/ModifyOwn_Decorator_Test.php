<?php
/**
 * Юнит-тесты ModifyOwn_Decorator.
 *
 * Матрица tests/покрытие.md: can_modify() true, если автор == текущий пользователь.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Demo_Entity;
use CPM\v3\Core\ModifyOwn_Decorator;

class ModifyOwn_Decorator_Test extends Cpm_TestCase {

	public function test_can_modify_true_for_author() {
		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 12 );

		$dec = new ModifyOwn_Decorator( new Demo_Entity( array( 'author' => 12 ) ) );
		$this->assertTrue( $this->invoke_protected( $dec, 'can_modify' ) );
	}

	public function test_can_modify_false_for_foreign_author() {
		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 12 );

		$dec = new ModifyOwn_Decorator( new Demo_Entity( array( 'author' => 99 ) ) );
		$this->assertFalse( $this->invoke_protected( $dec, 'can_modify' ) );
	}
}
