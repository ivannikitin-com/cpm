<?php
/**
 * Юнит-тесты Task.
 *
 * Матрица tests/покрытие.md: get_wp_args() (мета _start/_due/_completed/
 * _completed_on/_completed_by/_task_privacy); get_project() при parent =
 * список/проект/задача.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\Project;
use CPM\v3\Core\Task;
use CPM\v3\Core\Task_List;

class Task_Test extends Cpm_TestCase {

	public function test_cpt_and_defaults() {
		$this->assertSame( 'cpm_task', Task::CPT );
		$task = new Task();
		$this->assertSame( 'task', $task->get_type() );
		$this->assertSame( 0, $task->completed );
		$this->assertSame( 'no', $task->task_privacy );
	}

	public function test_get_wp_args_adds_task_meta() {
		$task = new Task(
			array(
				'id'           => 20,
				'parent'       => 5,
				'start'        => '2019-11-18 00:00:00',
				'due'          => '2019-11-19 00:00:00',
				'completed'    => 1,
				'completed_on' => '2019-11-20 12:00:00',
				'completed_by' => 12,
				'task_privacy' => 'yes',
			)
		);

		$args = $this->invoke_protected( $task, 'get_wp_args' );

		$this->assertSame( Task::CPT, $args['post_type'] );
		$this->assertSame( '2019-11-18 00:00:00', $args['meta_input']['_start'] );
		$this->assertSame( '2019-11-19 00:00:00', $args['meta_input']['_due'] );
		$this->assertSame( 1, $args['meta_input']['_completed'] );
		$this->assertSame( '2019-11-20 12:00:00', $args['meta_input']['_completed_on'] );
		$this->assertSame( 12, $args['meta_input']['_completed_by'] );
		$this->assertSame( 'yes', $args['meta_input']['_task_privacy'] );
	}

	public function test_get_project_uses_project_id_when_set() {
		$project = new Project( array( 'id' => 10 ) );
		$core    = $this->createMock( Core_Manager::class );
		$core->expects( $this->once() )
			->method( 'load_list' )
			->with( 'project', array( 'id' => 10 ) )
			->willReturn( array( $project ) );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'get_post_type' )->never();
		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' );

		$task = new Task( array( 'id' => 20, 'parent' => 5, 'project_id' => 10 ) );
		$this->assertSame( $project, $task->get_project() );
	}

	public function test_get_project_when_parent_is_task_list() {
		$project = new Project( array( 'id' => 10, 'title' => 'Acme' ) );
		$list    = new Task_List(
			array(
				'id'            => 5,
				'title'         => 'Backlog',
				'slug'          => 'backlog',
				'project_id'    => 10,
				'project_title' => 'Acme',
				'project_slug'  => 'acme',
			)
		);

		$core = $this->createMock( Core_Manager::class );
		$core->method( 'load_list' )->willReturnCallback(
			function ( $type, $args ) use ( $project, $list ) {
				if ( 'task_list' === $type && 5 === (int) $args['id'] ) {
					return array( $list );
				}
				if ( 'project' === $type && 10 === (int) $args['id'] ) {
					return array( $project );
				}
				return array();
			}
		);
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'get_post_type' )->with( 5 )->andReturn( Task_List::CPT );
		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' );

		$task = new Task( array( 'id' => 20, 'parent' => 5 ) );
		$this->assertSame( $project, $task->get_project() );
		$this->assertSame( 10, $task->project_id );
		$this->assertSame( 5, $task->task_list_id );
		$this->assertSame( 'Backlog', $task->task_list_title );
	}

	public function test_get_project_when_parent_is_project() {
		$project = new Project( array( 'id' => 10 ) );
		$core    = $this->createMock( Core_Manager::class );
		$core->method( 'load_list' )
			->with( 'project', array( 'id' => 10 ) )
			->willReturn( array( $project ) );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'get_post_type' )->with( 10 )->andReturn( Project::CPT );
		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' );

		$task = new Task( array( 'id' => 20, 'parent' => 10 ) );
		$this->assertSame( $project, $task->get_project() );
		$this->assertSame( 10, $task->project_id );
	}

	public function test_get_project_when_parent_is_task() {
		$project     = new Project( array( 'id' => 10 ) );
		$parent_task = new Task( array( 'id' => 21, 'project_id' => 10 ) );

		$core = $this->createMock( Core_Manager::class );
		$core->method( 'load_list' )->willReturnCallback(
			function ( $type, $args ) use ( $project, $parent_task ) {
				if ( 'task' === $type && 21 === (int) $args['id'] ) {
					return array( $parent_task );
				}
				if ( 'project' === $type && 10 === (int) $args['id'] ) {
					return array( $project );
				}
				return array();
			}
		);
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'get_post_type' )->with( 21 )->andReturn( Task::CPT );
		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' );

		$task = new Task( array( 'id' => 20, 'parent' => 21 ) );
		$this->assertSame( $project, $task->get_project() );
		$this->assertSame( 10, $task->project_id );
	}

	public function test_sql_reads_assigned_into_team_and_task_meta() {
		$sql = Task::$SQL;
		$this->assertStringContainsString( 'cpm_task', $sql );
		$this->assertStringContainsString( '_assigned', $sql );
		$this->assertStringContainsString( 'COALESCE', $sql );
		$this->assertStringContainsString( '_task_privacy', $sql );
		$this->assertStringContainsString( 'task_list_id', $sql );
	}
}
