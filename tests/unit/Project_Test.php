<?php
/**
 * Юнит-тесты Project.
 *
 * Матрица tests/покрытие.md: get_wp_args() (post_type, _cpm_coordinator, _project_active);
 * archive()/unarchive()/is_active(); set_coordinator() сохраняет; delete_entity()
 * удаляет in_cpm_user_role подготовленным запросом; delete() запрещён не-админу.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\AccessDeniedException;
use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\Project;

class Project_Test extends Cpm_TestCase {

	/**
	 * @return \PHPUnit\Framework\MockObject\MockObject|Core_Manager
	 */
	private function mock_core_manager() {
		$core = $this->createMock( Core_Manager::class );
		$core->method( 'get_entity_types' )->willReturn( array( 'project' => 'Project' ) );
		$core->method( 'load_list' )->willReturn( array() );
		$this->set_core_manager( $core );
		return $core;
	}

	private function mock_update_save() {
		\WP_Mock::userFunction( 'wp_slash' )->andReturnUsing(
			function ( $value ) {
				return $value;
			}
		);
		\WP_Mock::userFunction( 'wp_update_post' )->andReturn( 10 );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
	}

	public function test_cpt_and_get_type() {
		$this->assertSame( 'cpm_project', Project::CPT );
		$project = new Project();
		$this->assertSame( 'project', $project->get_type() );
		$this->assertSame( $project, $project->get_project() );
	}

	public function test_get_wp_args_adds_post_type_and_project_meta() {
		$project = new Project(
			array(
				'id'          => 10,
				'title'       => 'Acme',
				'coordinator' => 7,
				'active'      => 'yes',
				'team'        => '"12":manager',
				'thumbnail_id'=> 3,
			)
		);

		$args = $this->invoke_protected( $project, 'get_wp_args' );

		$this->assertSame( Project::CPT, $args['post_type'] );
		$this->assertSame( 7, $args['meta_input']['_cpm_coordinator'] );
		$this->assertSame( 'yes', $args['meta_input']['_project_active'] );
		$this->assertSame( '"12":manager', $args['meta_input']['_team'] );
		$this->assertSame( 3, $args['meta_input']['_thumbnail_id'] );
	}

	public function test_is_active_archive_unarchive() {
		$core = $this->mock_core_manager();
		$core->expects( $this->exactly( 2 ) )->method( 'flush' );
		$this->mock_update_save();

		$project = new Project( array( 'id' => 10, 'active' => 'yes' ) );
		$this->assertTrue( $project->is_active() );

		\WP_Mock::expectAction( 'cpm_core_entity_update_project', $project );
		\WP_Mock::expectAction( 'cpm_core_entity_update', $project );
		$project->archive();
		$this->assertFalse( $project->is_active() );
		$this->assertSame( 'no', $project->active );

		\WP_Mock::expectAction( 'cpm_core_entity_update_project', $project );
		\WP_Mock::expectAction( 'cpm_core_entity_update', $project );
		$project->unarchive();
		$this->assertTrue( $project->is_active() );
		$this->assertSame( 'yes', $project->active );
	}

	public function test_set_coordinator_saves() {
		$core = $this->mock_core_manager();
		$core->expects( $this->once() )->method( 'flush' );
		$this->mock_update_save();

		$project = new Project( array( 'id' => 10, 'coordinator' => 1 ) );
		\WP_Mock::expectAction( 'cpm_core_entity_update_project', $project );
		\WP_Mock::expectAction( 'cpm_core_entity_update', $project );
		$project->set_coordinator( 22 );

		$this->assertSame( 22, $project->coordinator );
	}

	public function test_delete_throws_for_non_administrator() {
		$this->mock_core_manager();

		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 12 );
		\WP_Mock::userFunction( 'user_can' )->with( 12, 'manage_options' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_delete_post' )->never();

		$project = new Project(
			array(
				'id'   => 10,
				'team' => '"12":manager',
			)
		);

		$this->expectException( AccessDeniedException::class );
		$project->delete();
	}

	public function test_delete_allowed_for_administrator() {
		$core = $this->mock_core_manager();
		$core->expects( $this->once() )->method( 'flush' );

		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 1 );
		\WP_Mock::userFunction( 'user_can' )->with( 1, 'manage_options' )->andReturn( true );
		\WP_Mock::userFunction( 'wp_delete_post' )->once()->with( 10, true );

		$wpdb          = \Mockery::mock();
		$wpdb->prefix  = 'wp_';
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( 'DELETE FROM wp_cpm_user_role WHERE project_id = %d', 10 )
			->andReturn( 'DELETE FROM wp_cpm_user_role WHERE project_id = 10' );
		$wpdb->shouldReceive( 'query' )
			->once()
			->with( 'DELETE FROM wp_cpm_user_role WHERE project_id = 10' )
			->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		$project = new Project( array( 'id' => 10 ) );
		\WP_Mock::expectAction( 'cpm_core_entity_delete_project', $project );
		\WP_Mock::expectAction( 'cpm_core_entity_delete', $project );
		$project->delete();

		unset( $GLOBALS['wpdb'] );
		$this->assertActionsCalled();
	}

	public function test_delete_entity_deletes_legacy_roles_with_prepare() {
		\WP_Mock::userFunction( 'wp_delete_post' )->once()->with( 42, true );

		$wpdb          = \Mockery::mock();
		$wpdb->prefix  = 'wp_';
		$wpdb->shouldReceive( 'prepare' )
			->once()
			->with( 'DELETE FROM wp_cpm_user_role WHERE project_id = %d', 42 )
			->andReturn( 'PREPARED' );
		$wpdb->shouldReceive( 'query' )->once()->with( 'PREPARED' )->andReturn( 1 );
		$GLOBALS['wpdb'] = $wpdb;

		$project = new Project( array( 'id' => 42 ) );
		$this->invoke_protected( $project, 'delete_entity' );

		unset( $GLOBALS['wpdb'] );
		$this->assertConditionsMet();
	}

	public function test_sql_contains_coalesce_team_and_project_meta() {
		$sql = Project::$SQL;
		$this->assertStringContainsString( 'COALESCE', $sql );
		$this->assertStringContainsString( "_team", $sql );
		$this->assertStringContainsString( '{cpm_user_role}', $sql );
		$this->assertStringContainsString( '_cpm_coordinator', $sql );
		$this->assertStringContainsString( '_project_active', $sql );
		$this->assertStringContainsString( 'cpm_project', $sql );
		$this->assertStringContainsString( 'HAVING', $sql );
	}
}
