<?php
/**
 * Интеграционные тесты REST: вложения.
 *
 * Проверяет маршруты cpm/v1/attachment: JSON-метаданные, файл-маршрут (ветки
 * ошибок — 404/403), поля ответа. Успешный /file вызывает exit (бинарная
 * отдача), поэтому в PHPUnit не выполняется — он покрыт юнитами ядра
 * (Attachment::validate_upload/create_from_upload) и проверяется на стенде вручную.
 *
 * Выполняется ТОЛЬКО против тестовой БД cpm_test.
 */

namespace CPM\v3\Tests;

use WP_UnitTestCase;

class Attachment_REST_Test extends WP_UnitTestCase {

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
	 * @var string
	 */
	private $tmp_file;

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

		// Временный файл для фикстуры вложения (расширение обязательно).
		$this->tmp_file = tempnam( sys_get_temp_dir(), 'cpm-rest-' ) . '.txt';
		file_put_contents( $this->tmp_file, 'CPM fixture content' );
	}

	public function tearDown(): void {
		if ( $this->tmp_file && file_exists( $this->tmp_file ) ) {
			unlink( $this->tmp_file );
		}
		parent::tearDown();
	}

	private function do_request( $method, $route, $params = array() ) {
		$request = new \WP_REST_Request( $method, $route );
		if ( $params ) {
			$request->set_body_params( $params );
		}
		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Создаёт проект и реальный файл-вложение, привязанное к проекту.
	 *
	 * @return array { project_id: int, attachment_id: int }
	 */
	private function create_project_with_attachment() {
		$response = $this->do_request(
			'POST',
			'/cpm/v1/project',
			array(
				'title'       => 'Attachment Project',
				'coordinator' => $this->admin_id,
			)
		);
		$this->assertSame( 201, $response->get_status() );
		$project_id = (int) $response->get_data()['id'];

		$attachment_id = $this->factory()->attachment->create_upload_object( $this->tmp_file );
		$this->assertNotWPError( $attachment_id );
		update_post_meta( $attachment_id, '_project', $project_id );

		return array(
			'project_id'    => $project_id,
			'attachment_id' => (int) $attachment_id,
		);
	}

	public function test_routes_are_registered() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/cpm/v1/attachment', $routes );
		$this->assertArrayHasKey( '/cpm/v1/attachment/(?P<id>\d+)', $routes );
		$this->assertArrayHasKey( '/cpm/v1/attachment/(?P<id>\d+)/file', $routes );
	}

	public function test_get_item_returns_json_metadata() {
		$fixture = $this->create_project_with_attachment();
		$id      = $fixture['attachment_id'];

		$response = $this->do_request( 'GET', '/cpm/v1/attachment/' . $id );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( $id, (int) $data['id'] );
		$this->assertSame( $fixture['project_id'], (int) $data['project_id'] );
		$this->assertArrayHasKey( 'mime_type', $data );
		$this->assertArrayHasKey( 'file_url', $data );
		$this->assertStringContainsString( '/cpm/v1/attachment/' . $id . '/file', $data['file_url'] );
	}

	public function test_get_item_returns_404_for_unknown() {
		$response = $this->do_request( 'GET', '/cpm/v1/attachment/999999' );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_get_item_returns_404_without_project() {
		// Вложение без мета _project не входит ни в один проект.
		$id = $this->factory()->attachment->create_upload_object( $this->tmp_file );
		$this->assertNotWPError( $id );

		$response = $this->do_request( 'GET', '/cpm/v1/attachment/' . (int) $id );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_file_route_returns_404_for_unknown() {
		$response = $this->do_request( 'GET', '/cpm/v1/attachment/999999/file' );
		$this->assertSame( 404, $response->get_status() );
	}

	public function test_file_route_returns_404_for_absent_file_on_disk() {
		$fixture = $this->create_project_with_attachment();

		// Удаляем физический файл: метаданные остаются, файла нет.
		$file = get_attached_file( $fixture['attachment_id'] );
		if ( $file && file_exists( $file ) ) {
			unlink( $file );
		}

		$response = $this->do_request( 'GET', '/cpm/v1/attachment/' . $fixture['attachment_id'] . '/file' );
		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'cpm_file_missing', $response->get_data()['code'] );
	}
}
