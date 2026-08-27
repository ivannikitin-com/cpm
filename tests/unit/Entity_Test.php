<?php
/**
 * Юнит-тесты Entity.
 *
 * Матрица tests/покрытие.md: get_type(); get_wp_args(); save() insert/update и flush();
 * delete() рекурсия и отложенный flush(); хуки create/update/delete; delete_entity();
 * get_thumbnail() по цепочке родительства.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\Demo_Entity;
use CPM\v3\Core\Entity;
use CPM\v3\Core\EntitySaveException;
use CPM\v3\Core\IEntity;

class Entity_Test extends Cpm_TestCase {

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|Core_Manager
	 */
	private function mock_core_manager() {
		$core = $this->createMock( Core_Manager::class );
		$core->method( 'get_entity_types' )->willReturn( array( 'demo_entity' => 'Demo_Entity' ) );
		$core->method( 'load_list' )->willReturn( array() );
		$this->set_core_manager( $core );
		return $core;
	}

	public function test_implements_ientity() {
		$entity = new Demo_Entity();
		$this->assertInstanceOf( IEntity::class, $entity );
		$this->assertInstanceOf( Entity::class, $entity );
	}

	public function test_get_type_strips_namespace_and_lowercases() {
		$entity = new Demo_Entity();
		$this->assertSame( 'demo_entity', $entity->get_type() );
	}

	public function test_get_wp_args_contains_team_and_thumbnail_meta() {
		$entity = new Demo_Entity(
			array(
				'id'           => 3,
				'menu_order'   => 2,
				'author'       => 9,
				'content'      => 'body',
				'created_at'   => '2024-01-01 00:00:00',
				'slug'         => 'slug',
				'parent'       => 1,
				'title'        => 'Title',
				'thumbnail_id' => 8,
				'team'         => '"12":manager',
			)
		);

		$args = $entity->expose_wp_args();

		$this->assertSame( 3, $args['ID'] );
		$this->assertSame( 2, $args['menu_order'] );
		$this->assertSame( 9, $args['post_author'] );
		$this->assertSame( 'body', $args['post_content'] );
		$this->assertSame( '2024-01-01 00:00:00', $args['post_date'] );
		$this->assertSame( 'slug', $args['post_name'] );
		$this->assertSame( 1, $args['post_parent'] );
		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertSame( 'Title', $args['post_title'] );
		$this->assertSame( '"12":manager', $args['meta_input']['_team'] );
		$this->assertSame( 8, $args['meta_input']['_thumbnail_id'] );
	}

	public function test_save_calls_wp_insert_post_when_id_empty() {
		$core = $this->mock_core_manager();
		$core->expects( $this->once() )->method( 'flush' );

		\WP_Mock::userFunction( 'wp_slash' )->andReturnUsing(
			function ( $value ) {
				return $value;
			}
		);
		\WP_Mock::userFunction( 'wp_insert_post' )->once()->andReturn( 42 );
		\WP_Mock::userFunction( 'wp_update_post' )->never();
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );

		$entity = new Demo_Entity( array( 'title' => 'New' ) );
		\WP_Mock::expectAction( 'cpm_core_entity_create_demo_entity', $entity );
		\WP_Mock::expectAction( 'cpm_core_entity_create', $entity );
		$entity->save();

		$this->assertSame( 42, $entity->id );
		$this->assertActionsCalled();
	}

	public function test_save_calls_wp_update_post_when_id_set() {
		$core = $this->mock_core_manager();
		$core->expects( $this->once() )->method( 'flush' );

		\WP_Mock::userFunction( 'wp_slash' )->andReturnUsing(
			function ( $value ) {
				return $value;
			}
		);
		\WP_Mock::userFunction( 'wp_insert_post' )->never();
		\WP_Mock::userFunction( 'wp_update_post' )->once()->andReturn( 7 );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );

		$entity = new Demo_Entity( array( 'id' => 7, 'title' => 'Old' ) );
		\WP_Mock::expectAction( 'cpm_core_entity_update_demo_entity', $entity );
		\WP_Mock::expectAction( 'cpm_core_entity_update', $entity );
		$entity->save();

		$this->assertSame( 7, $entity->id );
		$this->assertActionsCalled();
	}

	public function test_save_throws_entity_save_exception_on_failure() {
		$this->mock_core_manager();

		\WP_Mock::userFunction( 'wp_slash' )->andReturnUsing(
			function ( $value ) {
				return $value;
			}
		);
		\WP_Mock::userFunction( 'wp_insert_post' )->once()->andReturn( 0 );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );

		$entity = new Demo_Entity( array( 'title' => 'Fail' ) );

		$this->expectException( EntitySaveException::class );
		$entity->save();
	}

	public function test_delete_recurses_children_and_defers_flush() {
		$child = $this->getMockBuilder( Demo_Entity::class )
			->setConstructorArgs( array( array( 'id' => 2 ) ) )
			->onlyMethods( array( 'delete' ) )
			->getMock();
		$child->expects( $this->once() )->method( 'delete' )->with( true );

		$core = $this->createMock( Core_Manager::class );
		$core->method( 'get_entity_types' )->willReturn( array( 'demo_entity' => 'Demo_Entity' ) );
		$core->method( 'load_list' )->willReturn( array( $child ) );
		$core->expects( $this->once() )->method( 'flush' );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'wp_delete_post' )->once()->with( 1, true );

		$entity = new Demo_Entity( array( 'id' => 1 ) );
		\WP_Mock::expectAction( 'cpm_core_entity_delete_demo_entity', $entity );
		\WP_Mock::expectAction( 'cpm_core_entity_delete', $entity );
		$entity->delete();

		$this->assertActionsCalled();
	}

	public function test_delete_skips_hooks_and_flush_when_recursion() {
		$core = $this->mock_core_manager();
		$core->expects( $this->never() )->method( 'flush' );

		\WP_Mock::userFunction( 'wp_delete_post' )->once()->with( 5, true );

		$entity = new Demo_Entity( array( 'id' => 5 ) );
		$entity->delete( true );
		$this->assertConditionsMet();
	}

	public function test_delete_entity_calls_wp_delete_post_with_force_delete() {
		\WP_Mock::userFunction( 'wp_delete_post' )->once()->with( 11, CPM_FORCE_DELETE );

		$entity = new Demo_Entity( array( 'id' => 11 ) );
		$entity->expose_delete_entity();
		$this->assertConditionsMet();
	}

	public function test_get_thumbnail_walks_parent_chain() {
		$parent                    = new Demo_Entity( array( 'id' => 1, 'thumbnail_id' => 55 ) );
		$child                     = new Demo_Entity( array( 'id' => 2 ) );
		$child->parent_entity      = $parent;

		\WP_Mock::userFunction( 'wp_get_attachment_image_url' )
			->once()
			->with( 55, 'full' )
			->andReturn( 'http://img.test/p.png' );

		$this->assertSame( 'http://img.test/p.png', $child->get_thumbnail() );
	}

	public function test_get_thumbnail_uses_own_image_before_parent() {
		$parent                    = new Demo_Entity( array( 'id' => 1, 'thumbnail_id' => 55 ) );
		$child                     = new Demo_Entity( array( 'id' => 2, 'thumbnail_id' => 7 ) );
		$child->parent_entity      = $parent;

		\WP_Mock::userFunction( 'wp_get_attachment_image_url' )
			->once()
			->with( 7, 'medium' )
			->andReturn( 'http://img.test/own.png' );

		$this->assertSame( 'http://img.test/own.png', $child->get_thumbnail( 'medium' ) );
	}

	public function test_get_thumbnail_returns_empty_when_no_image() {
		$entity = new Demo_Entity();
		$this->assertSame( '', $entity->get_thumbnail() );
		$this->assertSame( '', $entity->get_thumbnail_url() );
	}

	public function test_set_thumbnail_saves() {
		$core = $this->mock_core_manager();
		$core->expects( $this->once() )->method( 'flush' );

		\WP_Mock::userFunction( 'wp_slash' )->andReturnUsing(
			function ( $value ) {
				return $value;
			}
		);
		\WP_Mock::userFunction( 'wp_update_post' )->once()->andReturn( 3 );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );

		$entity = new Demo_Entity( array( 'id' => 3 ) );
		\WP_Mock::expectAction( 'cpm_core_entity_update_demo_entity', $entity );
		\WP_Mock::expectAction( 'cpm_core_entity_update', $entity );
		$entity->set_thumbnail( 88 );

		$this->assertSame( 88, $entity->get_thumbnail_id() );
	}
}
