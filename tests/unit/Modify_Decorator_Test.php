<?php
/**
 * Юнит-тесты Modify_Decorator.
 *
 * Матрица tests/покрытие.md: can_modify() = true.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Demo_Entity;
use CPM\v3\Core\Modify_Decorator;

class Modify_Decorator_Test extends Cpm_TestCase {

	public function test_can_modify_is_true() {
		$dec = new Modify_Decorator( new Demo_Entity() );
		$this->assertTrue( $this->invoke_protected( $dec, 'can_modify' ) );
	}
}
