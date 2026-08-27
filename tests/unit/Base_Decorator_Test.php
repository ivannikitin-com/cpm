<?php
/**
 * Юнит-тесты Base_Decorator и делегирования.
 *
 * Матрица tests/покрытие.md: __get/__set/__call; запись свойств только при
 * can_modify(); save()/delete() при разрешении и AccessDeniedException при запрете;
 * get_type()/get_project() делегируются.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\AccessDeniedException;
use CPM\v3\Core\Demo_Entity;
use CPM\v3\Core\Modify_Decorator;
use CPM\v3\Core\ReadOnly_Decorator;

class Base_Decorator_Test extends Cpm_TestCase {

	public function test_get_type_and_get_project_are_delegated() {
		$entity = new Demo_Entity( array( 'id' => 3, 'title' => 'T' ) );
		$dec    = new Modify_Decorator( $entity );

		$this->assertSame( 'demo_entity', $dec->get_type() );
		$this->assertNull( $dec->get_project() );
		$this->assertSame( 'T', $dec->title );
	}

	public function test_set_and_call_allowed_when_can_modify() {
		$entity = new Demo_Entity( array( 'id' => 3, 'title' => 'T', 'thumbnail_id' => 3 ) );
		$dec    = new Modify_Decorator( $entity );

		$dec->title = 'New';
		$this->assertSame( 'New', $entity->title );
		$this->assertSame( 3, $dec->get_thumbnail_id() );
	}

	public function test_set_forbidden_does_not_change_entity() {
		$entity = new Demo_Entity( array( 'id' => 3, 'title' => 'Keep' ) );
		$dec    = new ReadOnly_Decorator( $entity );

		try {
			$dec->title = 'Changed';
			$this->fail( 'Expected AccessDeniedException' );
		} catch ( AccessDeniedException $e ) {
			$this->assertSame( 'Keep', $entity->title );
		}
	}

	public function test_save_and_delete_allowed_delegate_to_entity() {
		$entity = $this->getMockBuilder( Demo_Entity::class )
			->setConstructorArgs( array( array( 'id' => 3 ) ) )
			->onlyMethods( array( 'save', 'delete' ) )
			->getMock();
		$entity->expects( $this->once() )->method( 'save' );
		$entity->expects( $this->once() )->method( 'delete' )->with( false );

		$dec = new Modify_Decorator( $entity );
		$dec->save();
		$dec->delete();
	}

	public function test_save_and_delete_forbidden_do_not_touch_entity() {
		$entity = $this->getMockBuilder( Demo_Entity::class )
			->setConstructorArgs( array( array( 'id' => 3 ) ) )
			->onlyMethods( array( 'save', 'delete' ) )
			->getMock();
		$entity->expects( $this->never() )->method( 'save' );
		$entity->expects( $this->never() )->method( 'delete' );

		$dec = new ReadOnly_Decorator( $entity );

		try {
			$dec->save();
			$this->fail( 'Expected AccessDeniedException on save' );
		} catch ( AccessDeniedException $e ) {
			// expected
		}

		try {
			$dec->delete();
			$this->fail( 'Expected AccessDeniedException on delete' );
		} catch ( AccessDeniedException $e ) {
			// expected
		}
	}
}
