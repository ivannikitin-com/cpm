<?php
/**
 * Class Entity_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

use CPM\v3\Core\IEntity;

/**
 * Типовой CRUD-контроллер сущности ядра.
 *
 * Конкретные контроллеры описывают тип и схему полей (см. docs/rest-api/схемы.md),
 * а этот класс реализует список/одну сущность/создание/обновление/удаление.
 *
 * Спецификация: docs/rest-api/архитектура.md, docs/rest-api/маршруты.md
 */
abstract class Entity_Controller extends Base_Controller {

	/**
	 * Маршрут сущности в URL (после namespace), по умолчанию — тип ядра.
	 *
	 * @return string
	 */
	protected function get_rest_base() {
		return $this->get_type();
	}

	/**
	 * Схема полей сущности: имя => параметры.
	 *
	 * @return array[] {
	 *     @type string 'type'    Тип значения: string|integer|boolean|object.
	 *     @type bool   'read'    Поле отдаётся в ответе.
	 *     @type bool   'create'  Поле принимается при создании.
	 *     @type bool   'update'  Поле принимается при обновлении.
	 *     @type bool   'required' Поле обязательно при создании.
	 * }
	 */
	abstract protected function get_fields();

	/**
	 * {@inheritdoc}
	 */
	public function register_routes() {
		$base = $this->get_rest_base();
		$writable = $this->supports_write();

		$collection = array(
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'permission_check' ),
			),
		);
		if ( $writable ) {
			$collection[] = array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'permission_check' ),
			);
		}
		register_rest_route( $this->namespace, '/' . $base, $collection );

		$item = array(
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
		);
		if ( $writable ) {
			$item[] = array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param ) && (int) $param > 0;
						},
					),
				),
			);
			$item[] = array(
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
			);
		}
		register_rest_route( $this->namespace, '/' . $base . '/(?P<id>\d+)', $item );
	}

	/**
	 * Общие поля Entity (docs/rest-api/схемы.md).
	 *
	 * @return array[]
	 */
	protected function entity_fields() {
		return array(
			'id'           => array( 'type' => 'integer', 'read' => true ),
			'parent'       => array( 'type' => 'integer', 'read' => true, 'create' => true ),
			'author'       => array( 'type' => 'integer', 'read' => true ),
			'title'        => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
			'content'      => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
			'created_at'   => array( 'type' => 'string', 'read' => true ),
			'slug'         => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
			'menu_order'   => array( 'type' => 'integer', 'read' => true, 'create' => true, 'update' => true ),
			'thumbnail_id' => array( 'type' => 'integer', 'read' => true ),
			'team'         => array( 'type' => 'object', 'read' => true ),
		);
	}

	/**
	 * Поля Project_Entity (project_id/project_title/project_slug).
	 * project_id передаётся при создании (нужен ACL для новой сущности);
	 * project_title/project_slug — только чтение.
	 *
	 * @return array[]
	 */
	protected function project_entity_fields() {
		return array(
			'project_id'    => array( 'type' => 'integer', 'read' => true, 'create' => true ),
			'project_title' => array( 'type' => 'string', 'read' => true ),
			'project_slug'  => array( 'type' => 'string', 'read' => true ),
		);
	}

	/**
	 * Схема для чтения доступна ли операция создания сущности данного типа.
	 * По умолчанию — да (кроме activity: см. Activity_Controller).
	 *
	 * @return bool
	 */
	protected function supports_write() {
		return true;
	}

	/**
	 * Список сущностей.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$args = $this->build_list_args( $request );

		$items = $this->run_core(
			function () use ( $args ) {
				return $this->get_core()->load_list( $this->get_type(), $args );
			}
		);
		if ( is_wp_error( $items ) ) {
			return $items;
		}

		$data = array();
		foreach ( $items as $item ) {
			$data[] = $this->serialize_item( $item );
		}

		$response = new \WP_REST_Response( $data, 200 );

		// Заголовки пагинации (решение №14).
		if ( isset( $args['per_page'] ) && $args['per_page'] > 0 ) {
			$total = $this->run_core(
				function () use ( $args ) {
					return $this->get_core()->count_list( $this->get_type(), $args );
				}
			);
			if ( ! is_wp_error( $total ) ) {
				$response->header( 'X-WP-Total', (int) $total );
				$response->header( 'X-WP-TotalPages', (int) ceil( $total / $args['per_page'] ) );
			}
		}

		return $response;
	}

	/**
	 * Одна сущность.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$entity = $this->get_entity_or_error( (int) $request['id'] );
		if ( is_wp_error( $entity ) ) {
			return $entity;
		}
		return new \WP_REST_Response( $this->serialize_item( $entity ), 200 );
	}

	/**
	 * Создание сущности.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_item( $request ) {
		$fields = $this->get_fields();
		$body   = $this->get_body_params( $request );

		$args = $this->extract_writable( $body, $fields, 'create' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}
		$required = $this->validate_required( $body, $fields );
		if ( is_wp_error( $required ) ) {
			return $required;
		}

		// Автор новой сущности — текущий пользователь (author — read-only поле).
		$args['author'] = get_current_user_id();

		$item = $this->run_core(
			function () use ( $args ) {
				return $this->get_core()->create( $this->get_type(), $args );
			}
		);
		if ( is_wp_error( $item ) ) {
			return $item;
		}

		return new \WP_REST_Response( $this->serialize_item( $item ), 201 );
	}

	/**
	 * Обновление сущности.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_item( $request ) {
		$entity = $this->get_entity_or_error( (int) $request['id'] );
		if ( is_wp_error( $entity ) ) {
			return $entity;
		}

		$fields = $this->get_fields();
		$body   = $this->get_body_params( $request );

		$args = $this->extract_writable( $body, $fields, 'update' );
		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$saved = $this->run_core(
			function () use ( $entity, $args ) {
				foreach ( $args as $name => $value ) {
					$entity->$name = $value; // Декоратор проверит can_modify().
				}
				$entity->save();
				return $entity;
			}
		);
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return new \WP_REST_Response( $this->serialize_item( $saved ), 200 );
	}

	/**
	 * Удаление сущности.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$entity = $this->get_entity_or_error( (int) $request['id'] );
		if ( is_wp_error( $entity ) ) {
			return $entity;
		}

		$result = $this->run_core(
			function () use ( $entity ) {
				$entity->delete();
				return true;
			}
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response( null, 204 );
	}

	/**
	 * Сущность по ID или WP_Error 404.
	 *
	 * @param int $id ID.
	 * @return IEntity|\WP_Error
	 */
	protected function get_entity_or_error( $id ) {
		$entity = $this->get_entity( $this->get_type(), $id );
		if ( ! $entity ) {
			return new \WP_Error(
				'cpm_not_found',
				'Объект не найден',
				array( 'status' => 404 )
			);
		}
		return $entity;
	}

	/**
	 * Параметры списка из запроса.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return array
	 */
	protected function build_list_args( $request ) {
		$args = array();

		$filters = array( 'id', 'parent', 'project_id', 'slug', 'task_list_id' );
		foreach ( $filters as $key ) {
			$value = $request->get_param( $key );
			if ( null !== $value && '' !== $value ) {
				$args[ $key ] = ( 'slug' === $key ) ? (string) $value : (int) $value;
			}
		}

		foreach ( array( 'per_page', 'page', 'offset' ) as $key ) {
			$value = $request->get_param( $key );
			if ( is_numeric( $value ) ) {
				$args[ $key ] = max( 0, (int) $value );
			}
		}

		$orderby = $request->get_param( 'orderby' );
		if ( $orderby ) {
			$args['orderby'] = sanitize_key( (string) $orderby );
			$order           = $request->get_param( 'order' );
			$args['order']   = in_array( $order, array( 'asc', 'desc' ), true ) ? $order : 'asc';
		}

		return $args;
	}

	/**
	 * Параметры тела запроса (JSON или form).
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return array
	 */
	protected function get_body_params( $request ) {
		$json = $request->get_json_params();
		if ( is_array( $json ) && $json ) {
			return $json;
		}
		$body = $request->get_body_params();
		return is_array( $body ) ? $body : array();
	}

	/**
	 * Сериализация сущности по схеме полей.
	 *
	 * @param IEntity $item Сущность (обёрнута декоратором).
	 * @return array
	 */
	protected function serialize_item( $item ) {
		$data = array();

		foreach ( $this->get_fields() as $name => $spec ) {
			if ( empty( $spec['read'] ) ) {
				continue;
			}
			if ( 'team' === $name && isset( $item->team ) ) {
				$data[ $name ] = $this->serialize_team( $item->team );
				continue;
			}
			$value = isset( $item->$name ) ? $item->$name : null;
			$data[ $name ] = $this->cast_value( $value, isset( $spec['type'] ) ? $spec['type'] : 'string' );
		}

		return $data;
	}

	/**
	 * Команда в ответе: карта { user_id: role }.
	 *
	 * @param object $team Экземпляр \CPM\v3\Core\Team.
	 * @return array
	 */
	protected function serialize_team( $team ) {
		$members = ( isset( $team->members ) && is_array( $team->members ) ) ? $team->members : array();
		$map     = array();
		foreach ( $members as $user_id => $role ) {
			$map[ (string) $user_id ] = (string) $role;
		}
		return $map;
	}

	/**
	 * Достаёт из тела только поля, разрешённые для операции.
	 * Неизвестные и read-only поля — 400 (решения №17–18).
	 *
	 * @param array  $body   Параметры тела.
	 * @param array  $fields Схема полей.
	 * @param string $op     create | update.
	 * @return array|\WP_Error
	 */
	protected function extract_writable( $body, $fields, $op ) {
		$args = array();

		foreach ( $body as $name => $value ) {
			if ( ! isset( $fields[ $name ] ) ) {
				return new \WP_Error(
					'cpm_invalid_field',
					sprintf( 'Неизвестное поле: %s', $name ),
					array( 'status' => 400 )
				);
			}
			$spec = $fields[ $name ];
			if ( empty( $spec[ $op ] ) ) {
				return new \WP_Error(
					'cpm_readonly_field',
					sprintf( 'Поле доступно только для чтения: %s', $name ),
					array( 'status' => 400 )
				);
			}

			$args[ $name ] = $this->sanitize_value(
				$value,
				isset( $spec['type'] ) ? $spec['type'] : 'string'
			);
		}

		return $args;
	}

	/**
	 * Проверка обязательных полей при создании.
	 *
	 * @param array $body   Параметры тела.
	 * @param array $fields Схема полей.
	 * @return true|\WP_Error
	 */
	protected function validate_required( $body, $fields ) {
		foreach ( $fields as $name => $spec ) {
			if ( empty( $spec['required'] ) ) {
				continue;
			}
			if ( ! array_key_exists( $name, $body ) || '' === $body[ $name ] ) {
				return new \WP_Error(
					'cpm_missing_field',
					sprintf( 'Обязательное поле не задано: %s', $name ),
					array( 'status' => 400 )
				);
			}
		}
		return true;
	}

	/**
	 * Приводит значение к типу поля.
	 *
	 * @param mixed  $value Значение.
	 * @param string $type  Тип.
	 * @return mixed
	 */
	protected function sanitize_value( $value, $type ) {
		switch ( $type ) {
			case 'integer':
				return (int) $value;
			case 'boolean':
				return (bool) $value;
			case 'object':
				return $value;
			default:
				return (string) $value;
		}
	}

	/**
	 * Приводит значение к типу для ответа.
	 *
	 * @param mixed  $value Значение.
	 * @param string $type  Тип.
	 * @return mixed
	 */
	protected function cast_value( $value, $type ) {
		if ( null === $value ) {
			return null;
		}
		switch ( $type ) {
			case 'integer':
				return (int) $value;
			case 'boolean':
				return (bool) $value;
			default:
				return (string) $value;
		}
	}
}
