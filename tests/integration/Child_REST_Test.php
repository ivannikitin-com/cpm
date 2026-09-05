<?php
/**
 * Интеграционные тесты REST: дочерние сущности (task_list, task).
 *
 * Проверяет общий Entity_Controller для сущностей проекта: создание через
 * Core_Manager::create() с project_id, чтение, обновление, удаление.
 *
 * Выполняется ТОЛЬКО против тестовой БД cpm_test.
 */

namespace CPM\v3\Tests;

use WP_UnitTestCase;

class Child_REST_Test extends WP_UnitTestCase {

	/**
	 * Легаси-таблица ролей создана.
	 *
	 * @var bool
	 */
	private static $legacy_table_ready = false;

	/**
	 * @var int
	 */
	private $admin_id;

	/**
	 * @beforeClass
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		if ( self::$legacy_table_ready ) {
			return;
		}
		global $wpdb;
		$table = $wpdb->prefix . 'cpm_user_role';
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$table} (
				ID int NOT NULL AUTO_INCREMENT,
				project_id int NOT NULL,
				user_id int NOT NULL,
				role varchar(50) NOT NULL DEFAULT '',
				component varchar(50) NOT NULL DEFAULT '',
				PRIMARY KEY  (ID)
			)"
		);
		self::$legacy_table_ready = true;
	}

	public function setUp(): void {
		parent::setUp();
		$this->admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );
	}

	private function do_request( $method, $route, $params = array() ) {
		$request = new \WP_REST_Request( $method, $route );
		if ( $params ) {
			$request->set_body_params( $params );
		}
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Создаёт проект через REST.
	 *
	 * @return array
	 */
	private function create_project() {
		$response = $this->do_request(
			'POST',
			'/cpm/v1/project',
			array(
				'title'       => 'Child REST Project',
				'coordinator' => $this->admin_id,
			)
		);
		$this->assertSame( 201, $response->get_status() );
		return $response->get_data();
	}

	public function test_task_list_create_get_update_delete() {
		$project = $this->create_project();
		$pid     = (int) $project['id'];

		$create = $this->do_request(
			'POST',
			'/cpm/v1/task_list',
			array(
				'title'      => 'List A',
				'parent'     => $pid,
				'project_id' => $pid,
			)
		);
		$this->assertSame( 201, $create->get_status(), 'Не удалось создать task_list' );
		$list = $create->get_data();
		$this->assertNotEmpty( $list['id'] );
		$this->assertSame( 'List A', $list['title'] );
		$this->assertSame( $pid, (int) $list['project_id'] );

		$get = $this->do_request( 'GET', '/cpm/v1/task_list/' . (int) $list['id'] );
		$this->assertSame( 200, $get->get_status() );
		$this->assertSame( 'List A', $get->get_data()['title'] );

		$update = $this->do_request(
			'POST',
			'/cpm/v1/task_list/' . (int) $list['id'],
			array( 'title' => 'List B' )
		);
		$this->assertSame( 200, $update->get_status() );
		$this->assertSame( 'List B', $update->get_data()['title'] );

		$delete = $this->do_request( 'DELETE', '/cpm/v1/task_list/' . (int) $list['id'] );
		$this->assertSame( 204, $delete->get_status() );

		$gone = $this->do_request( 'GET', '/cpm/v1/task_list/' . (int) $list['id'] );
		$this->assertSame( 404, $gone->get_status() );
	}

	public function test_task_create_requires_parent_or_project() {
		$response = $this->do_request( 'POST', '/cpm/v1/task', array( 'title' => 'No parent' ) );
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_task_create_get_list_by_project() {
		$project = $this->create_project();
		$pid     = (int) $project['id'];

		$list_resp = $this->do_request(
			'POST',
			'/cpm/v1/task_list',
			array(
				'title'      => 'Sprint',
				'parent'     => $pid,
				'project_id' => $pid,
			)
		);
		$list = $list_resp->get_data();
		$lid  = (int) $list['id'];

		$create = $this->do_request(
			'POST',
			'/cpm/v1/task',
			array(
				'title'      => 'Task 1',
				'parent'     => $lid,
				'project_id' => $pid,
				'due'        => '2024-01-31 00:00:00',
			)
		);
		$this->assertSame( 201, $create->get_status(), 'Не удалось создать task' );
		$task = $create->get_data();
		$this->assertSame( $lid, (int) $task['parent'] );
		$this->assertSame( $pid, (int) $task['project_id'] );
		$this->assertSame( '2024-01-31 00:00:00', $task['due'] );

		// Список задач проекта.
		$request = new \WP_REST_Request( 'GET', '/cpm/v1/task' );
		$request->set_query_params( array( 'project_id' => $pid ) );
		$list_resp = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $list_resp->get_status() );
		$this->assertCount( 1, $list_resp->get_data() );
	}

	public function test_activity_routes_are_read_only() {
		// Создание/изменение/удаление activity не зарегистрированы: POST → 404.
		$response = $this->do_request( 'POST', '/cpm/v1/activity', array( 'content' => 'x' ) );
		$this->assertSame( 404, $response->get_status() );
	}
}
