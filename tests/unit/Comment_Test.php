<?php
/**
 * Юнит-тесты Comment.
 *
 * Матрица tests/покрытие.md: save()/get_wp_args()/delete_entity() под comments;
 * get_project() по цепочке родителя.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Comment;
use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\EntitySaveException;
use CPM\v3\Core\Project;
use CPM\v3\Core\Task;

class Comment_Test extends Cpm_TestCase {

	public function test_get_type_and_no_cpt() {
		$comment = new Comment();
		$this->assertSame( 'comment', $comment->get_type() );
		$this->assertFalse( defined( 'CPM\v3\Core\Comment::CPT' ) );
	}

	public function test_get_wp_args_maps_comment_fields() {
		$comment = new Comment(
			array(
				'id'      => 7,
				'parent'  => 20,
				'content' => 'Hello',
				'author'  => 12,
				'created_at' => '2024-01-02 03:04:05',
				'files'   => array( 6932 ),
			)
		);

		$args = $this->invoke_protected( $comment, 'get_wp_args' );

		$this->assertSame( 7, $args['comment_ID'] );
		$this->assertSame( 20, $args['comment_post_ID'] );
		$this->assertSame( 'Hello', $args['comment_content'] );
		$this->assertSame( 12, $args['user_id'] );
		$this->assertSame( 'comment', $args['comment_type'] );
		$this->assertArrayNotHasKey( 'post_type', $args );
	}

	public function test_save_inserts_comment_and_files_meta() {
		$core = $this->createMock( Core_Manager::class );
		$core->expects( $this->once() )->method( 'flush' );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'wp_slash' )->andReturnUsing(
			function ( $value ) {
				return $value;
			}
		);
		\WP_Mock::userFunction( 'wp_insert_comment' )->once()->andReturn( 44 );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'update_comment_meta' )->once()->with( 44, '_files', array( 9 ) );

		$comment = new Comment(
			array(
				'parent'  => 20,
				'content' => 'Hi',
				'author'  => 12,
				'files'   => array( 9 ),
			)
		);
		\WP_Mock::expectAction( 'cpm_core_entity_create_comment', $comment );
		\WP_Mock::expectAction( 'cpm_core_entity_create', $comment );
		$comment->save();

		$this->assertSame( 44, $comment->id );
	}

	public function test_save_updates_existing_comment() {
		$core = $this->createMock( Core_Manager::class );
		$core->expects( $this->once() )->method( 'flush' );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'wp_slash' )->andReturnUsing(
			function ( $value ) {
				return $value;
			}
		);
		\WP_Mock::userFunction( 'wp_insert_comment' )->never();
		\WP_Mock::userFunction( 'wp_update_comment' )->once()->andReturn( 1 );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'update_comment_meta' )->once()->with( 7, '_files', array() );

		$comment = new Comment( array( 'id' => 7, 'parent' => 20, 'content' => 'Edited' ) );
		\WP_Mock::expectAction( 'cpm_core_entity_update_comment', $comment );
		\WP_Mock::expectAction( 'cpm_core_entity_update', $comment );
		$comment->save();

		$this->assertSame( 7, $comment->id );
	}

	public function test_save_throws_on_insert_failure() {
		$this->set_core_manager( $this->createMock( Core_Manager::class ) );
		\WP_Mock::userFunction( 'wp_slash' )->andReturnUsing(
			function ( $value ) {
				return $value;
			}
		);
		\WP_Mock::userFunction( 'wp_insert_comment' )->andReturn( false );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );

		$this->expectException( EntitySaveException::class );
		( new Comment( array( 'content' => 'x' ) ) )->save();
	}

	public function test_delete_entity_deletes_comment() {
		\WP_Mock::userFunction( 'wp_delete_comment' )->once()->with( 7, true );
		$comment = new Comment( array( 'id' => 7 ) );
		$this->invoke_protected( $comment, 'delete_entity' );
		$this->assertConditionsMet();
	}

	public function test_get_project_when_parent_is_project() {
		$project = new Project( array( 'id' => 10 ) );
		$core    = $this->createMock( Core_Manager::class );
		$core->method( 'load_by_post_id' )->with( 10 )->willReturn( $project );
		$this->set_core_manager( $core );

		$comment = new Comment( array( 'id' => 1, 'parent' => 10 ) );
		$this->assertSame( $project, $comment->get_project() );
	}

	public function test_get_project_returns_null_for_unknown_parent() {
		$core = $this->createMock( Core_Manager::class );
		$core->method( 'load_by_post_id' )->willReturn( null );
		$this->set_core_manager( $core );

		$comment = new Comment( array( 'parent' => 3331 ) );
		$this->assertNull( $comment->get_project() );
	}

	/**
	 * @dataProvider provider_parent_entities
	 * @param object $parent Родительская сущность CPM.
	 */
	public function test_get_project_for_cpm_parent_types( $parent ) {
		$project = new Project( array( 'id' => 10 ) );
		$core    = $this->createMock( Core_Manager::class );
		$core->method( 'load_by_post_id' )->willReturn( $parent );
		$core->method( 'load_list' )->willReturn( array( $project ) );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' );

		$comment = new Comment( array( 'parent' => $parent->id ) );
		$this->assertSame( $project, $comment->get_project() );
	}

	/**
	 * Типы parent из реальной БД: задача, сообщение, список, заметка.
	 *
	 * @return array
	 */
	public function provider_parent_entities() {
		return array(
			'task'      => array( new Task( array( 'id' => 20, 'project_id' => 10 ) ) ),
			'message'   => array( new \CPM\v3\Core\Message( array( 'id' => 30, 'project_id' => 10 ) ) ),
			'task_list' => array( new \CPM\v3\Core\Task_List( array( 'id' => 40, 'project_id' => 10 ) ) ),
			'note'      => array( new \CPM\v3\Core\Note( array( 'id' => 50, 'project_id' => 10 ) ) ),
		);
	}

	public function test_get_project_returns_null_without_parent() {
		$comment = new Comment();
		$this->assertNull( $comment->get_project() );
	}

	public function test_query_rows_uses_get_comments_and_files_meta() {
		$wp_comment = (object) array(
			'comment_ID'      => 5,
			'comment_content' => 'Hi',
			'user_id'         => 12,
			'comment_date'    => '2024-01-01 00:00:00',
			'comment_post_ID' => 20,
		);
		\WP_Mock::userFunction( 'get_comments' )->once()->with(
			\Mockery::on(
				function ( $query ) {
					return 'comment' === $query['type'] && 20 === $query['post_id'];
				}
			)
		)->andReturn( array( $wp_comment ) );
		\WP_Mock::userFunction( 'get_comment_meta' )->once()->with( 5, '_files', true )->andReturn( array() );

		$rows = Comment::query_rows( array( 'parent' => 20 ) );
		$this->assertCount( 1, $rows );
		$this->assertSame( 5, $rows[0]['id'] );
		$this->assertSame( 20, $rows[0]['parent'] );
		$this->assertSame( array(), $rows[0]['files'] );
	}

	public function test_empty_files_normalized_to_array() {
		$comment = new Comment( array( 'files' => array() ) );
		$this->assertSame( array(), $comment->files );
	}
}
