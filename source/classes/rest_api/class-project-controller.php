<?php
/**
 * Class Project_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

use CPM\v3\Core\Team;

/**
 * REST-контроллер проекта: CRUD + экшены (archive/unarchive/coordinator/team/thumbnail).
 *
 * Спецификация: docs/rest-api/маршруты.md, docs/rest-api/схемы.md
 */
class Project_Controller extends Entity_Controller {

	/**
	 * Тип сущности ядра.
	 *
	 * @return string
	 */
	protected function get_type() {
		return 'project';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_fields() {
		return array(
			'id'           => array( 'type' => 'integer', 'read' => true ),
			'parent'       => array( 'type' => 'integer', 'read' => true ),
			'author'       => array( 'type' => 'integer', 'read' => true ),
			'title'        => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true, 'required' => true ),
			'content'      => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
			'created_at'   => array( 'type' => 'string', 'read' => true ),
			'slug'         => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
			'menu_order'   => array( 'type' => 'integer', 'read' => true, 'create' => true, 'update' => true ),
			'thumbnail_id' => array( 'type' => 'integer', 'read' => true ),
			'team'         => array( 'type' => 'object', 'read' => true ),
			'coordinator'  => array( 'type' => 'integer', 'read' => true, 'create' => true ),
			'active'       => array( 'type' => 'string', 'read' => true ),
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function register_routes() {
		parent::register_routes();

		$base = $this->get_rest_base();

		// Экшены ядра отдельными маршрутами (решения №20–21).
		$actions = array(
			'archive'     => 'archive_item',
			'unarchive'   => 'unarchive_item',
			'coordinator' => 'coordinator_item',
			'team'        => 'team_item',
			'thumbnail'   => 'thumbnail_item',
		);
		foreach ( $actions as $action => $method ) {
			register_rest_route(
				$this->namespace,
				'/' . $base . '/(?P<id>\d+)/' . $action,
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, $method ),
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
	}

	/**
	 * POST /project/{id}/archive
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function archive_item( $request ) {
		return $this->run_action( $request, 'archive' );
	}

	/**
	 * POST /project/{id}/unarchive
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function unarchive_item( $request ) {
		return $this->run_action( $request, 'unarchive' );
	}

	/**
	 * POST /project/{id}/coordinator  { "user_id": 12 }
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function coordinator_item( $request ) {
		$entity = $this->get_entity_or_error( (int) $request['id'] );
		if ( is_wp_error( $entity ) ) {
			return $entity;
		}
		$user_id = $request->get_param( 'user_id' );
		if ( ! is_numeric( $user_id ) ) {
			return new \WP_Error(
				'cpm_invalid_param',
				'Параметр user_id обязателен',
				array( 'status' => 400 )
			);
		}

		$result = $this->run_core(
			function () use ( $entity, $user_id ) {
				$entity->set_coordinator( (int) $user_id );
				return $entity;
			}
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new \WP_REST_Response( $this->serialize_item( $result ), 200 );
	}

	/**
	 * POST /project/{id}/team  { "members": { "12": "manager", "15": "client" } }
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function team_item( $request ) {
		$entity = $this->get_entity_or_error( (int) $request['id'] );
		if ( is_wp_error( $entity ) ) {
			return $entity;
		}
		$members = $request->get_param( 'members' );
		if ( ! is_array( $members ) || ! $members ) {
			return new \WP_Error(
				'cpm_invalid_param',
				'Параметр members обязателен',
				array( 'status' => 400 )
			);
		}
		$team = $this->team_from_map( $members );
		if ( is_wp_error( $team ) ) {
			return $team;
		}

		$result = $this->run_core(
			function () use ( $entity, $team ) {
				$entity->team = $team; // Декоратор проверит can_modify().
				$entity->save();
				return $entity;
			}
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new \WP_REST_Response( $this->serialize_item( $result ), 200 );
	}

	/**
	 * POST /project/{id}/thumbnail  { "thumbnail_id": 55 }
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function thumbnail_item( $request ) {
		$entity = $this->get_entity_or_error( (int) $request['id'] );
		if ( is_wp_error( $entity ) ) {
			return $entity;
		}
		$thumbnail_id = $request->get_param( 'thumbnail_id' );
		if ( ! is_numeric( $thumbnail_id ) ) {
			return new \WP_Error(
				'cpm_invalid_param',
				'Параметр thumbnail_id обязателен',
				array( 'status' => 400 )
			);
		}

		$result = $this->run_core(
			function () use ( $entity, $thumbnail_id ) {
				$entity->set_thumbnail( (int) $thumbnail_id );
				return $entity;
			}
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new \WP_REST_Response( $this->serialize_item( $result ), 200 );
	}

	/**
	 * Выполняет простой экшен (archive/unarchive) над проектом.
	 *
	 * @param \WP_REST_Request $request Запрос.
	 * @param string           $method  Метод ядра.
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function run_action( $request, $method ) {
		$entity = $this->get_entity_or_error( (int) $request['id'] );
		if ( is_wp_error( $entity ) ) {
			return $entity;
		}

		$result = $this->run_core(
			function () use ( $entity, $method ) {
				$entity->$method();
				return $entity;
			}
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new \WP_REST_Response( $this->serialize_item( $result ), 200 );
	}

	/**
	 * Команда из карты участников с проверкой ролей.
	 *
	 * @param array $members Карта { user_id: role }.
	 * @return Team|\WP_Error
	 */
	private function team_from_map( $members ) {
		$allowed = array( 'manager', 'co_worker', 'client' );
		$pairs   = array();

		foreach ( $members as $user_id => $role ) {
			if ( ! is_numeric( $user_id ) || ! in_array( (string) $role, $allowed, true ) ) {
				return new \WP_Error(
					'cpm_invalid_param',
					'Некорректный состав команды: user_id и роль (manager/co_worker/client)',
					array( 'status' => 400 )
				);
			}
			$pairs[] = '"' . (int) $user_id . '":' . (string) $role;
		}

		return new Team( implode( ',', $pairs ) );
	}
}
