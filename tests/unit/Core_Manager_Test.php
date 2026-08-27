<?php
/**
 * Юнит-тесты Core_Manager.
 *
 * Матрица tests/покрытие.md: маппинг $entity_types; load_list() подставляет SQL
 * и фильтры через $wpdb->prepare(); create() вызывает save() и flush();
 * выбор декоратора — в rights_table_Test.php.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\AccessDeniedException;
use CPM\v3\Core\ACL;
use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\Modify_Decorator;
use CPM\v3\Core\Project;
use CPM\v3\Plugin;

class Core_Manager_Test extends Cpm_TestCase {

	public function test_entity_types_mapping() {
		$core  = $this->make_core();
		$types = $core->get_entity_types();

		$this->assertSame( 'Project', $types['project'] );
		$this->assertSame( 'Task_List', $types['task_list'] );
		$this->assertSame( 'Task', $types['task'] );
		$this->assertSame( 'Message', $types['message'] );
		$this->assertSame( 'Milestone', $types['milestone'] );
		$this->assertSame( 'Note', $types['note'] );
		$this->assertSame( 'Comment', $types['comment'] );
		$this->assertSame( 'Attachment', $types['attachment'] );
		$this->assertSame( 'Activity', $types['activity'] );
	}

	public function test_flush_calls_acl_flush() {
		\WP_Mock::userFunction( 'wp_cache_flush_group' )->once()->with( ACL::CACHE_GROUP );

		$core = $this->make_core();
		$core->flush();
		$this->assertConditionsMet();
	}

	public function test_get_instance_returns_constructed_manager() {
		$core = $this->make_core();
		$this->assertSame( $core, Core_Manager::get_instance() );
	}

	public function test_load_list_returns_empty_array_for_unknown_type() {
		$core = $this->make_core();
		$this->assertSame( array(), $core->load_list( 'unknown' ) );
	}

	public function test_load_list_returns_empty_array_when_no_rows() {
		$this->mock_current_user( 1, true );
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn( array() );

		$core = $this->make_core();
		$this->assertSame( array(), $core->load_list( 'project' ) );
	}

	public function test_load_list_uses_prepare_for_sql_placeholders_and_filters() {
		$this->mock_current_user( 15, false );
		$wpdb = $this->mock_wpdb();

		$prepared = array();
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing(
			function ( $query, $value ) use ( &$prepared ) {
				$prepared[] = array( $query, $value );
				return $this->fake_prepare( $query, $value );
			}
		);
		$wpdb->shouldReceive( 'get_results' )->once()->with(
			\Mockery::on(
				function ( $sql ) {
					$this->assertStringContainsString( 'wp_posts', $sql );
					$this->assertStringContainsString( 'wp_postmeta', $sql );
					$this->assertStringContainsString( 'wp_cpm_user_role', $sql );
					$this->assertStringContainsString( '( 0 OR team LIKE', $sql );
					$this->assertStringContainsString( '%"15"%', $sql );
					$this->assertStringContainsString( 'id = 10', $sql );
					$this->assertStringContainsString( "slug = 'acme'", $sql );
					return true;
				}
			),
			ARRAY_A
		)->andReturn( array() );

		$core = $this->make_core();
		$core->load_list(
			'project',
			array(
				'id'   => 10,
				'slug' => 'acme',
			)
		);

		$this->assertContains( array( '%s', '%"15"%' ), $prepared );
		$this->assertContains( array( '%d', 10 ), $prepared );
		$this->assertContains( array( '%s', 'acme' ), $prepared );
	}

	public function test_load_list_sets_is_admin_for_administrator() {
		$this->mock_current_user( 1, true );
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_results' )->once()->with(
			\Mockery::on(
				function ( $sql ) {
					$this->assertStringContainsString( '( 1 OR team LIKE', $sql );
					return true;
				}
			),
			ARRAY_A
		)->andReturn( array() );

		$core = $this->make_core();
		$core->load_list( 'project' );
	}

	public function test_load_list_returns_single_id_as_one_element_array() {
		$this->mock_current_user( 1, true );
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn(
			array(
				array(
					'id'    => 10,
					'title' => 'Acme',
					'team'  => '"12":manager',
				),
			)
		);

		$core = $this->make_core();
		$list = $core->load_list( 'project', array( 'id' => 10 ) );

		$this->assertCount( 1, $list );
		$this->assertInstanceOf( Modify_Decorator::class, $list[0] );
		$this->assertInstanceOf( Project::class, $list[0]->get_project() );
		$this->assertSame( 10, $list[0]->id );
		$this->assertSame( 'project', $list[0]->get_type() );
	}

	public function test_load_list_excludes_no_access_entities() {
		$this->mock_current_user( 12, false );
		$project = new Project(
			array(
				'id'   => 5,
				'team' => '"12":client',
			)
		);
		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( $project );

		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn(
			array(
				array(
					'id'         => 99,
					'project_id' => 5,
					'title'      => 'Hidden',
					'author'     => 1,
				),
			)
		);

		$core = $this->make_core();
		$this->assertSame( array(), $core->load_list( 'milestone' ) );
	}

	public function test_load_list_adds_parent_filter_to_having() {
		$this->mock_current_user( 1, true );
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_results' )->once()->with(
			\Mockery::on(
				function ( $sql ) {
					$this->assertStringContainsString( 'HAVING TRUE AND parent = 77', $sql );
					return true;
				}
			),
			ARRAY_A
		)->andReturn( array() );

		$core = $this->make_core();
		$core->load_list( 'task', array( 'parent' => 77 ) );
	}

	public function test_create_saves_entity_and_flushes() {
		$this->mock_current_user( 1, true );
		\WP_Mock::userFunction( 'wp_slash' )->andReturnUsing(
			function ( $value ) {
				return $value;
			}
		);
		\WP_Mock::userFunction( 'wp_insert_post' )->once()->andReturn( 55 );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_flush_group' )->twice()->with( ACL::CACHE_GROUP );

		$core   = $this->make_core();
		$result = $core->create(
			'project',
			array(
				'title' => 'New',
				'team'  => '"1":manager',
			)
		);

		$this->assertInstanceOf( Modify_Decorator::class, $result );
		$this->assertSame( 55, $result->id );
		$this->assertSame( 'New', $result->title );
	}

	public function test_create_throws_for_unknown_type() {
		$core = $this->make_core();
		$this->expectException( AccessDeniedException::class );
		$core->create( 'unknown' );
	}

	public function test_create_rejects_activity() {
		$this->mock_current_user( 1, true );
		$core = $this->make_core();
		$this->expectException( AccessDeniedException::class );
		$core->create( 'activity', array( 'content' => 'nope' ) );
	}

	public function test_load_list_uses_query_rows_for_comments() {
		$this->mock_current_user( 1, true );
		\WP_Mock::userFunction( 'get_comments' )->once()->andReturn(
			array(
				(object) array(
					'comment_ID'      => 5,
					'comment_content' => 'Hi',
					'user_id'         => 1,
					'comment_date'    => '2024-01-01 00:00:00',
					'comment_post_ID' => 20,
				),
			)
		);
		\WP_Mock::userFunction( 'get_comment_meta' )->andReturn( array() );

		$core = $this->make_core();
		$list = $core->load_list( 'comment', array( 'parent' => 20 ) );

		$this->assertCount( 1, $list );
		$this->assertInstanceOf( Modify_Decorator::class, $list[0] );
		$this->assertSame( 5, $list[0]->id );
		$this->assertSame( 'comment', $list[0]->get_type() );
	}

	public function test_load_by_post_id_maps_cpt_to_entity() {
		$this->mock_current_user( 1, true );
		\WP_Mock::userFunction( 'get_post_type' )->once()->with( 10 )->andReturn( 'cpm_project' );
		$wpdb = $this->mock_wpdb();
		$wpdb->shouldReceive( 'get_results' )->once()->andReturn(
			array(
				array(
					'id'    => 10,
					'title' => 'Acme',
					'team'  => '"1":manager',
				),
			)
		);

		$core   = $this->make_core();
		$entity = $core->load_by_post_id( 10 );

		$this->assertInstanceOf( Modify_Decorator::class, $entity );
		$this->assertSame( 10, $entity->id );
		$this->assertSame( 'project', $entity->get_type() );
	}

	public function test_create_throws_when_right_is_read_only() {
		$this->mock_current_user( 12, false );
		$core = $this->make_core();

		$this->expectException( AccessDeniedException::class );
		$core->create(
			'project',
			array(
				'title' => 'Denied',
				'team'  => '"12":client',
			)
		);
	}

	public function test_create_throws_when_right_is_no_access() {
		$this->mock_current_user( 12, false );
		$project = new Project(
			array(
				'id'   => 5,
				'team' => '"12":client',
			)
		);
		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( $project );

		$core = $this->make_core();
		$this->expectException( AccessDeniedException::class );
		$core->create(
			'milestone',
			array(
				'title'      => 'Hidden',
				'project_id' => 5,
			)
		);
	}

	/**
	 * @return Core_Manager
	 */
	private function make_core() {
		new Plugin( $this->plugin_dir(), 'http://cpm.test/' );
		return new Core_Manager();
	}

	/**
	 * @param int  $user_id ID пользователя.
	 * @param bool $is_admin Право manage_options.
	 */
	private function mock_current_user( $user_id, $is_admin ) {
		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( $user_id );
		\WP_Mock::userFunction( 'user_can' )->andReturn( $is_admin );
	}

	/**
	 * @return \Mockery\MockInterface
	 */
	private function mock_wpdb() {
		$wpdb           = \Mockery::mock();
		$wpdb->posts    = 'wp_posts';
		$wpdb->postmeta = 'wp_postmeta';
		$wpdb->prefix   = 'wp_';
		$wpdb->shouldReceive( 'prepare' )->andReturnUsing(
			function ( $query, $value ) {
				return $this->fake_prepare( $query, $value );
			}
		)->byDefault();
		$GLOBALS['wpdb'] = $wpdb;
		return $wpdb;
	}

	/**
	 * Упрощённый аналог $wpdb->prepare() для юнитов.
	 *
	 * @param string $query Плейсхолдер %s / %d.
	 * @param mixed  $value Значение.
	 * @return string
	 */
	private function fake_prepare( $query, $value ) {
		if ( '%d' === $query ) {
			return (string) (int) $value;
		}
		$escaped = str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), (string) $value );
		return "'" . $escaped . "'";
	}
}
