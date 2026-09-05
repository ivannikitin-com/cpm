<?php
/**
 * Интеграционные тесты REST: проект.
 *
 * Проверяет маршруты cpm/v1/project: список, одну сущность, создание,
 * обновление, удаление, экшены (archive/unarchive/coordinator/team/thumbnail).
 *
 * Матрица docs/rest-api/тесты.md: регистрация маршрутов, сериализация,
 * маппинг ошибок, пагинация/сортировка, экшены, права.
 *
 * Выполняется ТОЛЬКО против тестовой БД cpm_test (см. tests/конфигурация.md).
 */

namespace CPM\v3\Tests;

use WP_UnitTestCase;

class Project_REST_Test extends WP_UnitTestCase {

	/**
	 * Администратор для тестов.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Обычный участник (manager), создаваемый в отдельных тестах.
	 *
	 * @var int
	 */
	private $manager_id;

	/**
	 * Легаси-таблица ролей создана.
	 *
	 * @var bool
	 */
	private static $legacy_table_ready = false;

	/**
	 * Создаёт пустую легаси-таблицу ролей (как на реальных инсталляциях после
	 * старой CPM). SQL-выборки ядра читают её через COALESCE, поэтому в чистой
	 * тестовой БД таблица должна существовать (см. tests/фикстуры.md).
	 *
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
		$this->manager_id = $this->factory()->user->create();
		wp_set_current_user( $this->admin_id );
	}

	/**
	 * Выполняет REST-запрос от текущего пользователя.
	 *
	 * @param string $method HTTP-метод.
	 * @param string $route  Маршрут (например, /cpm/v1/project).
	 * @param array  $params Параметры.
	 * @return \WP_REST_Response
	 */
	private function do_request( $method, $route, $params = array() ) {
		$request = new \WP_REST_Request( $method, $route );
		if ( $params ) {
			$request->set_body_params( $params );
		}
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Создаёт проект через REST как текущий пользователь.
	 *
	 * @param array $params Параметры создания.
	 * @return array
	 */
	private function create_project( $params = array() ) {
		$defaults = array(
			'title'       => 'REST Project',
			'content'     => 'Описание',
			'menu_order'  => 1,
			'coordinator' => $this->admin_id,
		);
		$response = $this->do_request( 'POST', '/cpm/v1/project', array_merge( $defaults, $params ) );
		$this->assertSame( 201, $response->get_status(), 'Создание проекта должно вернуть 201' );
		$data = $response->get_data();
		$this->assertIsArray( $data );
		return $data;
	}

	public function test_routes_are_registered() {
		$routes = rest_get_server()->get_routes();
		foreach ( array( 'project', 'task_list', 'task', 'message', 'milestone', 'note', 'comment', 'activity' ) as $type ) {
			$this->assertArrayHasKey( '/cpm/v1/' . $type, $routes, 'Нет маршрута списка: ' . $type );
			$this->assertArrayHasKey( '/cpm/v1/' . $type . '/(?P<id>\d+)', $routes, 'Нет маршрута сущности: ' . $type );
		}
		$this->assertArrayHasKey( '/cpm/v1/project/(?P<id>\d+)/archive', $routes );
		$this->assertArrayHasKey( '/cpm/v1/project/(?P<id>\d+)/team', $routes );
	}

	public function test_create_project_returns_entity() {
		$data = $this->create_project();

		$this->assertNotEmpty( $data['id'] );
		$this->assertSame( 'REST Project', $data['title'] );
		$this->assertSame( 'Описание', $data['content'] );
		$this->assertSame( (int) $this->admin_id, (int) $data['coordinator'] );
		$this->assertArrayHasKey( 'created_at', $data );
		$this->assertSame( array(), $data['team'] );
	}

	public function test_create_requires_title() {
		$response = $this->do_request( 'POST', '/cpm/v1/project', array( 'content' => 'no title' ) );
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_create_rejects_unknown_field() {
		$response = $this->do_request(
			'POST',
			'/cpm/v1/project',
			array(
				'title' => 'X',
				'foo'   => 'bar',
			)
		);
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_create_rejects_readonly_field() {
		// active меняется только экшеном archive/unarchive, не через общий create.
		$response = $this->do_request(
			'POST',
			'/cpm/v1/project',
			array(
				'title'  => 'X',
				'active' => 'no',
			)
		);
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_get_item_returns_project() {
		$created  = $this->create_project();
		$response = $this->do_request( 'GET', '/cpm/v1/project/' . (int) $created['id'] );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( (int) $created['id'], (int) $response->get_data()['id'] );
	}

	public function test_get_item_returns_404_for_missing() {
		$response = $this->do_request( 'GET', '/cpm/v1/project/999999' );
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'cpm_not_found', $response->get_data()['code'] );
	}

	public function test_get_items_returns_list_and_pagination_headers() {
		$this->create_project( array( 'title' => 'One' ) );
		$this->create_project( array( 'title' => 'Two' ) );

		$request = new \WP_REST_Request( 'GET', '/cpm/v1/project' );
		$request->set_query_params( array( 'per_page' => 1 ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 1, $response->get_data() );
		$headers = $response->get_headers();
		$this->assertSame( 2, (int) $headers['X-WP-Total'] );
		$this->assertSame( 2, (int) $headers['X-WP-TotalPages'] );
	}

	public function test_update_item_changes_title() {
		$created  = $this->create_project( array( 'title' => 'Before' ) );
		$response = $this->do_request(
			'POST',
			'/cpm/v1/project/' . (int) $created['id'],
			array( 'title' => 'After' )
		);
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'After', $response->get_data()['title'] );
	}

	public function test_archive_action() {
		$created  = $this->create_project();
		$response = $this->do_request( 'POST', '/cpm/v1/project/' . (int) $created['id'] . '/archive' );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'no', $response->get_data()['active'] );
	}

	public function test_unarchive_action() {
		$created  = $this->create_project();
		$id       = (int) $created['id'];
		$this->do_request( 'POST', '/cpm/v1/project/' . $id . '/archive' );
		$response = $this->do_request( 'POST', '/cpm/v1/project/' . $id . '/unarchive' );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'yes', $response->get_data()['active'] );
	}

	public function test_coordinator_action() {
		$created  = $this->create_project();
		$other    = $this->factory()->user->create();
		$response = $this->do_request(
			'POST',
			'/cpm/v1/project/' . (int) $created['id'] . '/coordinator',
			array( 'user_id' => $other )
		);
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( (int) $other, (int) $response->get_data()['coordinator'] );
	}

	public function test_team_action_sets_members() {
		$created  = $this->create_project();
		$member   = $this->factory()->user->create();
		$response = $this->do_request(
			'POST',
			'/cpm/v1/project/' . (int) $created['id'] . '/team',
			array(
				'members' => array(
					$this->admin_id => 'manager',
					$member         => 'client',
				),
			)
		);
		$this->assertSame( 200, $response->get_status() );
		$team = $response->get_data()['team'];
		$this->assertArrayHasKey( (string) $this->admin_id, $team );
		$this->assertSame( 'manager', $team[ (string) $this->admin_id ] );
	}

	public function test_team_action_rejects_bad_role() {
		$created = $this->create_project();
		$member  = $this->factory()->user->create();
		$response = $this->do_request(
			'POST',
			'/cpm/v1/project/' . (int) $created['id'] . '/team',
			array(
				'members' => array(
					$member => 'superuser',
				),
			)
		);
		$this->assertSame( 400, $response->get_status() );
	}

	public function test_delete_project_as_administrator() {
		$created = $this->create_project();
		$response = $this->do_request( 'DELETE', '/cpm/v1/project/' . (int) $created['id'] );
		$this->assertSame( 204, $response->get_status() );
	}

	public function test_unauthenticated_is_denied() {
		wp_set_current_user( 0 );
		$response = $this->do_request( 'GET', '/cpm/v1/project' );
		$this->assertSame( 401, $response->get_status() );
	}
}
