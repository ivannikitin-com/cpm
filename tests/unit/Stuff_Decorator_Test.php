<?php
/**
 * Юнит-тесты Stuff_Decorator.
 *
 * Матрица tests/покрытие.md: can_modify() — сотрудник и автор; can_read() —
 * сотрудник; CLIENT → запрет.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\AccessDeniedException;
use CPM\v3\Core\ACL;
use CPM\v3\Core\Demo_Entity;
use CPM\v3\Core\Project;
use CPM\v3\Core\Stuff_Decorator;

class Stuff_Decorator_Test extends Cpm_TestCase {

	/**
	 * @param string $role Роль в команде проекта.
	 * @param int    $user_id ID текущего пользователя.
	 * @param int    $author  Автор сущности.
	 * @return Stuff_Decorator
	 */
	private function decorator_for_role( $role, $user_id = 12, $author = 12 ) {
		$project = new Project(
			array(
				'id'   => 10,
				'team' => '"12":' . $role,
			)
		);
		$entity  = $this->getMockBuilder( Demo_Entity::class )
			->setConstructorArgs( array( array( 'id' => 4, 'author' => $author ) ) )
			->onlyMethods( array( 'get_project' ) )
			->getMock();
		$entity->method( 'get_project' )->willReturn( $project );

		\WP_Mock::userFunction( 'user_can' )->andReturn( false );
		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( $user_id );

		return new Stuff_Decorator( $entity );
	}

	public function test_can_modify_true_for_staff_author() {
		$dec = $this->decorator_for_role( ACL::MANAGER, 12, 12 );
		$this->assertTrue( $this->invoke_protected( $dec, 'can_read' ) );
		$this->assertTrue( $this->invoke_protected( $dec, 'can_modify' ) );
	}

	public function test_can_modify_false_for_staff_foreign_author() {
		$dec = $this->decorator_for_role( ACL::CO_WORKER, 12, 99 );
		$this->assertTrue( $this->invoke_protected( $dec, 'can_read' ) );
		$this->assertFalse( $this->invoke_protected( $dec, 'can_modify' ) );
	}

	public function test_client_cannot_read() {
		$dec = $this->decorator_for_role( ACL::CLIENT, 12, 12 );
		$this->assertFalse( $this->invoke_protected( $dec, 'can_read' ) );
		$this->assertFalse( $this->invoke_protected( $dec, 'can_modify' ) );

		$this->expectException( AccessDeniedException::class );
		$dec->get_type();
	}
}
