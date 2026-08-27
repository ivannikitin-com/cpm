<?php
/**
 * Юнит-тесты Activity.
 *
 * Матрица tests/покрытие.md: формирование текста; подписка на хуки;
 * get_activity() с фильтром comment_type=cpm_activity; старые записи как есть.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\AccessDeniedException;
use CPM\v3\Core\Activity;
use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\Project;
use CPM\v3\Core\Task;

class Activity_Test extends Cpm_TestCase {

	public function test_get_type_and_comment_type() {
		$activity = new Activity();
		$this->assertSame( 'activity', $activity->get_type() );
		$this->assertSame( 'cpm_activity', Activity::COMMENT_TYPE );
	}

	public function test_format_message_is_plain_text() {
		\WP_Mock::userFunction( 'wp_get_current_user' )->andReturn(
			(object) array( 'display_name' => 'Иван Никитин' )
		);

		$task    = new Task( array( 'id' => 20, 'title' => 'Настроить отправку форм CF7' ) );
		$message = Activity::format_message( 'update', $task );

		$this->assertSame(
			'Задача "Настроить отправку форм CF7" изменён(а) пользователем Иван Никитин',
			$message
		);
		$this->assertStringNotContainsString( '[cpm_', $message );
	}

	public function test_register_hooks_subscribes_to_entity_events() {
		\WP_Mock::expectActionAdded( 'cpm_core_entity_create', array( Activity::class, 'on_create' ) );
		\WP_Mock::expectActionAdded( 'cpm_core_entity_update', array( Activity::class, 'on_update' ) );
		\WP_Mock::expectActionAdded( 'cpm_core_entity_delete', array( Activity::class, 'on_delete' ) );

		Activity::register_hooks();
		Activity::register_hooks();
		$this->assertConditionsMet();
	}

	public function test_on_create_logs_to_project() {
		$project = new Project( array( 'id' => 10 ) );
		$task    = new Task( array( 'id' => 20, 'title' => 'Задача', 'project_id' => 10 ) );

		$core = $this->createMock( Core_Manager::class );
		$core->method( 'load_list' )->willReturn( array( $project ) );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' );
		\WP_Mock::userFunction( 'wp_get_current_user' )->andReturn(
			(object) array( 'display_name' => 'Иван' )
		);
		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 12 );
		\WP_Mock::userFunction( 'wp_insert_comment' )->once()->with(
			\Mockery::on(
				function ( $data ) {
					return Activity::COMMENT_TYPE === $data['comment_type']
						&& 10 === $data['comment_post_ID']
						&& false !== strpos( $data['comment_content'], 'Задача' )
						&& 12 === $data['user_id'];
				}
			)
		)->andReturn( 100 );

		Activity::on_create( $task );
		$this->assertConditionsMet();
	}

	public function test_on_create_skips_activity_entities() {
		\WP_Mock::userFunction( 'wp_insert_comment' )->never();
		Activity::on_create( new Activity( array( 'id' => 1, 'parent' => 10 ) ) );
		$this->assertConditionsMet();
	}

	public function test_get_activity_filters_comment_type() {
		$legacy = (object) array(
			'comment_ID'      => 8,
			'comment_content' => '[cpm_task_url id="20"] закрыта',
			'user_id'         => 3,
			'comment_date'    => '2019-01-01 00:00:00',
			'comment_post_ID' => 10,
		);

		\WP_Mock::userFunction( 'get_comments' )->once()->with(
			\Mockery::on(
				function ( $args ) {
					return 10 === $args['post_id']
						&& Activity::COMMENT_TYPE === $args['type']
						&& 'DESC' === $args['order'];
				}
			)
		)->andReturn( array( $legacy ) );

		$list = Activity::get_activity( 10 );
		$this->assertCount( 1, $list );
		$this->assertInstanceOf( Activity::class, $list[0] );
		$this->assertSame( '[cpm_task_url id="20"] закрыта', $list[0]->content );
	}

	public function test_save_is_read_only() {
		$this->expectException( AccessDeniedException::class );
		( new Activity() )->save();
	}

	public function test_log_inserts_cpm_activity_comment() {
		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 12 );
		\WP_Mock::userFunction( 'wp_insert_comment' )->once()->with(
			\Mockery::on(
				function ( $data ) {
					return 'cpm_activity' === $data['comment_type']
						&& 10 === $data['comment_post_ID']
						&& 'plain text' === $data['comment_content'];
				}
			)
		)->andReturn( 55 );

		$activity = new Activity();
		$this->assertSame( 55, $activity->log( 10, 'plain text' ) );
	}

	public function test_delete_entity_deletes_comment() {
		\WP_Mock::userFunction( 'wp_delete_comment' )->once()->with( 8, true );
		$activity = new Activity( array( 'id' => 8 ) );
		$this->invoke_protected( $activity, 'delete_entity' );
		$this->assertConditionsMet();
	}
}
