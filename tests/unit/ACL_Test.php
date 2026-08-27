<?php
/**
 * Юнит-тесты ACL.
 *
 * Матрица tests/покрытие.md: значения констант; get_role(): admin → ADMINISTRATOR
 * без дальнейших действий; сущность без проекта → NO_ROLE; участник → его роль;
 * не-участник → NO_ROLE; flush() сбрасывает кэш.
 *
 * get_user_projects(): кэш и запрос $wpdb с COALESCE.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\ACL;
use CPM\v3\Core\Demo_Entity;
use CPM\v3\Core\IEntity;

class ACL_Test extends Cpm_TestCase {

	public function test_role_constants() {
		$this->assertSame( 'administrator', ACL::ADMINISTRATOR );
		$this->assertSame( 'manager', ACL::MANAGER );
		$this->assertSame( 'co_worker', ACL::CO_WORKER );
		$this->assertSame( 'client', ACL::CLIENT );
		$this->assertSame( 'none', ACL::NO_ROLE );
	}

	public function test_get_role_returns_administrator_for_admin() {
		$entity = $this->createMock( IEntity::class );
		$entity->expects( $this->never() )->method( 'get_project' );

		\WP_Mock::userFunction( 'user_can' )->once()->with( 1, 'manage_options' )->andReturn( true );

		$this->assertSame( ACL::ADMINISTRATOR, ACL::get_role( $entity, 1 ) );
	}

	public function test_get_role_uses_current_user_when_user_id_null() {
		$entity = $this->createMock( IEntity::class );
		$entity->expects( $this->never() )->method( 'get_project' );

		\WP_Mock::userFunction( 'get_current_user_id' )->once()->andReturn( 7 );
		\WP_Mock::userFunction( 'user_can' )->once()->with( 7, 'manage_options' )->andReturn( true );

		$this->assertSame( ACL::ADMINISTRATOR, ACL::get_role( $entity ) );
	}

	public function test_get_role_returns_no_role_when_entity_has_no_project() {
		$entity = $this->createMock( IEntity::class );
		$entity->method( 'get_project' )->willReturn( null );

		\WP_Mock::userFunction( 'user_can' )->andReturn( false );

		$this->assertSame( ACL::NO_ROLE, ACL::get_role( $entity, 12 ) );
	}

	public function test_get_role_returns_member_role() {
		$project       = new Demo_Entity( array( 'id' => 10, 'team' => '"12":manager,"15":co_worker' ) );
		$entity        = $this->createMock( IEntity::class );
		$entity->method( 'get_project' )->willReturn( $project );

		\WP_Mock::userFunction( 'user_can' )->andReturn( false );

		$this->assertSame( ACL::MANAGER, ACL::get_role( $entity, 12 ) );
		$this->assertSame( ACL::CO_WORKER, ACL::get_role( $entity, 15 ) );
	}

	public function test_get_role_returns_no_role_for_non_member() {
		$project       = new Demo_Entity( array( 'id' => 10, 'team' => '"12":manager' ) );
		$entity        = $this->createMock( IEntity::class );
		$entity->method( 'get_project' )->willReturn( $project );

		\WP_Mock::userFunction( 'user_can' )->andReturn( false );

		$this->assertSame( ACL::NO_ROLE, ACL::get_role( $entity, 99 ) );
	}

	public function test_flush_flushes_object_cache_group() {
		\WP_Mock::userFunction( 'wp_cache_flush_group' )->once()->with( ACL::CACHE_GROUP );

		ACL::flush();
		$this->assertConditionsMet();
	}

	public function test_get_user_projects_returns_cache_hit() {
		\WP_Mock::userFunction( 'wp_cache_get' )
			->once()
			->with( 'cpm_acl_user_12', ACL::CACHE_GROUP )
			->andReturn( array( 11, 12, 15 ) );

		$this->assertSame( array( 11, 12, 15 ), ACL::get_user_projects( 12 ) );
	}

	public function test_get_user_projects_queries_wpdb_on_cache_miss() {
		\WP_Mock::userFunction( 'wp_cache_get' )
			->once()
			->with( 'cpm_acl_user_48', ACL::CACHE_GROUP )
			->andReturn( false );

		$wpdb           = \Mockery::mock();
		$wpdb->posts    = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->prefix   = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->once()->with( '%s', '%"48"%' )->andReturn( '\'%"48"%\'' );
		$wpdb->shouldReceive( 'get_col' )->once()->with(
			\Mockery::on(
				function ( $sql ) {
					$this->assertStringContainsString( 'wp_posts', $sql );
					$this->assertStringContainsString( 'wp_postmeta', $sql );
					$this->assertStringContainsString( 'wp_cpm_user_role', $sql );
					$this->assertStringContainsString( 'COALESCE', $sql );
					$this->assertStringContainsString( "cpm_project", $sql );
					$this->assertStringContainsString( '%"48"%', $sql );
					return true;
				}
			)
		)->andReturn( array( '11', '12', '15' ) );
		$GLOBALS['wpdb'] = $wpdb;

		\WP_Mock::userFunction( 'wp_cache_set' )
			->once()
			->with( 'cpm_acl_user_48', array( 11, 12, 15 ), ACL::CACHE_GROUP );

		$this->assertSame( array( 11, 12, 15 ), ACL::get_user_projects( 48 ) );
	}

	public function test_get_user_projects_caches_empty_result() {
		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );

		$wpdb           = \Mockery::mock();
		$wpdb->posts    = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->prefix   = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturn( '\'%"99"%\'' );
		$wpdb->shouldReceive( 'get_col' )->once()->andReturn( array() );
		$GLOBALS['wpdb'] = $wpdb;

		\WP_Mock::userFunction( 'wp_cache_set' )
			->once()
			->with( 'cpm_acl_user_99', array(), ACL::CACHE_GROUP );

		$this->assertSame( array(), ACL::get_user_projects( 99 ) );
	}
}
