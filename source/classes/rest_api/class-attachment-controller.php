<?php
/**
 * Class Attachment_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

use CPM\v3\Core\ACL;
use CPM\v3\Core\Attachment;

/**
 * REST-контроллер вложения.
 *
 * Особенности:
 * - создание — только multipart/form-data (POST /attachment), файл сохраняет ядро;
 * - JSON-метаданные — GET /attachment/{id};
 * - содержимое файла — GET /attachment/{id}/file.
 *
 * Спецификация: docs/rest-api/маршруты.md, docs/rest-api/загрузка-файлов.md
 */
class Attachment_Controller extends Entity_Controller {

	/**
	 * Тип сущности ядра.
	 *
	 * @return string
	 */
	protected function get_type() {
		return 'attachment';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_fields() {
		return array(
			'id'         => array( 'type' => 'integer', 'read' => true ),
			'parent'     => array( 'type' => 'integer', 'read' => true ),
			'author'     => array( 'type' => 'integer', 'read' => true ),
			'title'      => array( 'type' => 'string', 'read' => true ),
			'mime_type'  => array( 'type' => 'string', 'read' => true ),
			'is_image'   => array( 'type' => 'boolean', 'read' => true ),
			'width'      => array( 'type' => 'integer', 'read' => true ),
			'height'     => array( 'type' => 'integer', 'read' => true ),
			'size'       => array( 'type' => 'integer', 'read' => true ),
			'project_id' => array( 'type' => 'integer', 'read' => true ),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function register_routes() {
		$base = $this->get_rest_base();

		// Список (GET) — общий; создание переопределено в create_item (multipart).
		register_rest_route(
			$this->namespace,
			'/' . $base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $base . '/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'id' => array(
							'validate_callback' => function ( $param ) {
								return is_numeric( $param ) && (int) $param > 0;
							},
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'id' => array(
							'validate_callback' => function ( $param ) {
								return is_numeric( $param ) && (int) $param > 0;
							},
						),
					),
				),
			)
		);

		// Содержимое файла.
		register_rest_route(
			$this->namespace,
			'/' . $base . '/(?P<id>\d+)/file',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_file' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && (int) $param > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * Создание вложения из multipart-запроса.
	 *
	 * Поля формы: file, parent, project_id. JSON-создание не поддерживается.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		$files = $request->get_file_params();
		$file  = isset( $files['file'] ) ? $files['file'] : array();

		$parent     = (int) $request->get_param( 'parent' );
		$project_id = (int) $request->get_param( 'project_id' );

		// Права: вложение доступно только сотрудникам проекта (Stuff_Decorator).
		$allowed = $this->can_upload( $project_id );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$valid = Attachment::validate_upload( $file );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}

		$attachment_id = $this->run_core(
			function () use ( $file, $project_id, $parent ) {
				return Attachment::create_from_upload( $file, $project_id, $parent );
			}
		);
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$entity = $this->get_entity( 'attachment', $attachment_id );
		if ( ! $entity ) {
			return new \WP_Error(
				'cpm_not_found',
				'Вложение не найдено после загрузки',
				array( 'status' => 500 )
			);
		}

		return new \WP_REST_Response( $this->serialize_item( $entity ), 201 );
	}

	/**
	 * GET /attachment/{id}/file — содержимое файла.
	 *
	 * Права соблюдаются ядром: недоступное вложение load_list() не вернёт.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_file( $request ) {
		$entity = $this->get_entity_or_error( (int) $request['id'] );
		if ( is_wp_error( $entity ) ) {
			return $entity;
		}

		$download = rest_sanitize_boolean( $request->get_param( 'download' ) );
		$result   = $this->resolve_file_path( $entity );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->stream_file( $result['path'], $result['mime'], $download );
		// stream_file() завершает вывод через exit — сюда не возвращаемся.
	}

	/**
	 * Сериализация вложения: добавляет file_url к общим полям.
	 *
	 * @param object $item Вложение.
	 * @return array
	 */
	protected function serialize_item( $item ) {
		$data = parent::serialize_item( $item );

		$id             = (int) $item->id;
		$data['file_url'] = rest_url( 'cpm/v1/attachment/' . $id . '/file' );

		return $data;
	}

	/**
	 * Проверка права на загрузку в проект.
	 *
	 * Роль берётся из ядра (ACL) по проекту: нужен сотрудник (manager/co_worker)
	 * либо администратор. CLIENT и не-участник получают 403/404.
	 *
	 * @param int $project_id ID проекта.
	 * @return true|\WP_Error
	 */
	protected function can_upload( $project_id ) {
		if ( ! $project_id ) {
			return new \WP_Error(
				'cpm_invalid_param',
				'Параметр project_id обязателен',
				array( 'status' => 400 )
			);
		}

		$project = $this->get_entity( 'project', $project_id );
		if ( ! $project ) {
			return new \WP_Error(
				'cpm_not_found',
				'Проект не найден',
				array( 'status' => 404 )
			);
		}

		$role = ACL::get_role( $project );
		if ( in_array( $role, array( ACL::ADMINISTRATOR, ACL::MANAGER, ACL::CO_WORKER ), true ) ) {
			return true;
		}

		return new \WP_Error(
			'cpm_forbidden',
			'У вас нет прав на это действие',
			array( 'status' => 403 )
		);
	}

	/**
	 * Путь и MIME физического файла вложения.
	 *
	 * @param object $entity Вложение.
	 * @return array|\WP_Error
	 */
	protected function resolve_file_path( $entity ) {
		$path = get_attached_file( (int) $entity->id );
		if ( ! $path || ! file_exists( $path ) ) {
			return new \WP_Error(
				'cpm_file_missing',
				'Файл не найден на диске',
				array( 'status' => 404 )
			);
		}
		$mime = isset( $entity->mime_type ) && $entity->mime_type ? $entity->mime_type : 'application/octet-stream';
		return array(
			'path' => $path,
			'mime' => $mime,
		);
	}

	/**
	 * Отдаёт бинарный файл с заголовками и завершает вывод.
	 *
	 * Это маршрут с бинарным ответом (не JSON): вывод файла идёт напрямую,
	 * поэтому в PHPUnit вызываются только ветки ошибок (404 и т.п.).
	 *
	 * @param string $path     Путь к файлу.
	 * @param string $mime     MIME-тип.
	 * @param bool   $download true — Content-Disposition: attachment.
	 */
	protected function stream_file( $path, $mime, $download ) {
		$name = basename( $path );
		$size = (int) filesize( $path );

		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . $size );
		header( 'Cache-Control: private' );
		header( 'X-Content-Type-Options: nosniff' );

		if ( $download ) {
			header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		} else {
			header( 'Content-Disposition: inline' );
		}

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- бинарная отдача файла.
		exit;
	}
}
