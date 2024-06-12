<?php
/**
 * Класс контроллера REST API
 * Точка входа: http://127.0.0.1/wp-json/cpm/v3/
 */

namespace CPM\REST;

class Controller extends \WP_REST_Controller 
{
	/**
	 * Пространство имен REST API
	 */
	protected const NAMESPACE = 'cpm/v3';

    /**
     * Пространство имен REST API
     */
    protected $namespace = 'cpm/v3';

	/**
	 * Класс сущности
	 * @var string
	 */
	protected $entity_class;


	/**
	 * Разрешения на операции с ресурсом
	 * @var array
	 */
	protected $permissions = array();

    /**
     * Конструктор контролера
     */
	function __construct( $entity_class )
    {
		// Класс сущности, обрабатываемый этим контроллером
		$this->entity_class = $entity_class;

		// Пространство имен REST API базовый URL для данного контроллера
		$this->namespace = self::NAMESPACE;
		$this->rest_base = $this->entity_class::$rest_base;

		// Разрешение на операции REST API
		$this->permissions = $this->entity_class::get_wp_role_api_permissions();
	}

	/* ------------------------- Операции с ресурсами ------------------------- */
    /**
     * Регистрация маршрутов
     */
	function register_routes()
    {
        // Маршрут для получения списка ресурсов
		register_rest_route( $this->namespace, "/$this->rest_base", array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
            ),
			'schema' => array( $this, 'get_item_schema' ),
        ) );

        // Маршрут для получения конкретного ресурса
		register_rest_route( $this->namespace, "/$this->rest_base/(?P<id>[\w]+)", array(
				array(
					'methods'   => 'GET',
					'callback'  => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Возвращает схему ресурса.
	 *
	 * @return array
	 */
	function get_item_schema()
	{
		return $this->entity_class::get_rest_schema();
	}	

	/* ----------------------- Выполнение операций ------------------------- */
	/**
	 * Метод получает коллекцию ресурсов
	 * @param WP_REST_Request $request Текущий запрос
	 * @return array
	 */
	public function get_items( $request )
	{
		// TODO: реализовать передачу параметров в запрос сущности!
		$items = $this->entity_class::get_list();
		if ( empty( $items ) ) {
			return array();
		}

		// Возвращаем коллекцию ресурсов
		$data = array();
		foreach ( $items as $item ) {
			$data[] = $this->prepare_item_for_response( $item, $request );
		}

		\CPM\Plugin::get_instance()->log( self::class . ' get_items() result: ' . var_export( $data, true ), 'debug' );
		return $data;
	}

	/**
	 * Метод получает отдельный ресурс
	 * @param WP_REST_Request $request Текущий запрос
	 * @return array
	 */
	public function get_item( $request ) 
	{
		// ID требуемого ресурса
		$id = (int) $request['id'];

		// Получаем и возвращаем отдельный ресурс
		$item = $this->entity_class::get( $id );
		$result = ( $item ) ? $this->prepare_item_for_response( $item, $request ) : array();
		\CPM\Plugin::get_instance()->log( self::class . ' get_item() result: ' . var_export( $result, true ), 'debug' );
		return $result;
	}

	/**
	 * Метод создает новый ресурс
	 */
	public function create_item( $request ) {}

	/**
	 * Метод обновляет существующий ресурс
	 */
	public function update_item( $request ) {}

	/**
	 * Метод удаляет существующий ресурс
	 */
	public function delete_item( $request ) {}

	/**
	 * Собирает данные ресурса в соответствии со схемой ресурса
	 * @param Entity          $item	Объект ресурса, из которого будут взяты оригинальные данные
	 * @param WP_REST_Request $request	Текущий запрос
	 * @return array
	 */
	public function prepare_item_for_response( $item, $request ) 
	{
		return $this->entity_class::get_rest_item( $item );
	}
	
	 /* ----------------------- Права на выполнение операций ------------------------- */

	/**
	 * перед вызовом коллбэк функции проверяет есть ли право у текущего запроса 
	 * получать коллекцию ресурсов
	 */
	public function get_items_permissions_check( $request ) {
		return $this->check_api_permissions( 'read' );
	}

	/**
	 * перед вызовом коллбэк функции проверяет есть ли право у текущего запроса 
	 * получать отдельный ресурс
	 */
	public function get_item_permissions_check( $request ) {
		return $this->check_api_permissions( 'read' );
	}

	/**
	 * перед вызовом коллбэк функции проверяет есть ли право у текущего запроса 
	 * создавать ресурс 
	 */
	public function create_item_permissions_check( $request ) {
		return $this->check_api_permissions( 'create' );
	}

	/**
	 * перед вызовом коллбэк функции проверяет есть ли право у текущего запроса 
	 * обновлять ресурс 
	 */
	public function update_item_permissions_check( $request ) {
		return $this->check_api_permissions( 'update' );
	}

	/**
	 * перед вызовом коллбэк функции проверяет есть ли право у текущего запроса 
	 * обновлять ресурс 
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->check_api_permissions( 'delete' );
	}

	/* ----------------------- Права доступа на операцию ----------------------- */
	/**
	 * Проверяет права доступа текущего пользователя к ресурсу
	 * @param string $operation Имя операции
	 * @return bool|WP_Error
	 */
	protected function check_api_permissions( $operation )
	{
		// Текущий пользователь, который сделал запрос
		$user = wp_get_current_user();

		// Проверим каждую роль текущего пользователя, имеет ли права
		// эта роль на выполнение этого запроса
		$operation_allowed = false;
		foreach ( $user->roles as $role ) {
			if ( ! isset( $this->permissions [ $role ] ) ) {
				continue;	// Роли нет в списке разрешений	
			}

			// Разрешена ли текущая операция для данной роли?
			if ( in_array( $operation, $this->permissions[ $role ] ) ) {
				$operation_allowed = true;
				break;
			}
		}

		// Если не разрешено, возвращает ошибку
		if ( ! $operation_allowed )
			return new \WP_Error( 
				'rest_forbidden', 
				__( 'У вас нет прав на выполнение этой операции', CPM ), 
				array( 
					'status' => is_user_logged_in() ? 403 : 401
				) 
			);
		return true;
	}
}