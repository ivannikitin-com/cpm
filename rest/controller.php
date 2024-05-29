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
     * Конструктор контролера
     */
	function __construct( $entity_class )
    {
		$this->entity_class = $entity_class;
		$this->namespace = self::NAMESPACE;
		$this->rest_base = $entity_class::$rest_base;
	}

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
	 * Проверяет права доступа к ресурсу
	 * @param WP_REST_Request $request Текущий запрос.
	 * @return bool|WP_Error
	 */
	function get_items_permissions_check( $request )
	{
		if ( ! current_user_can( 'read' ) )
			return new WP_Error( 
				'rest_forbidden', 
				__( 'У вас нет прав для просмотра этого ресурса', CPM ), 
				array( 
					'status' => $this->error_status_code() 
				) 
			);
		return true;
	}

	/**
	 * Получает последние посты и отдает их в виде rest ответа.
	 *
	 * @param WP_REST_Request $request Текущий запрос.
	 * @return WP_Error|array
	 */
	function get_items( $request ){
		$data = [];

		$posts = get_posts( [
			'post_per_page' => 5,
		] );

		if ( empty( $posts ) )
			return $data;

		foreach( $posts as $post ){
			$response = $this->prepare_item_for_response( $post, $request );
			$data[] = $this->prepare_response_for_collection( $response );
		}

		return $data;
	}

	## Проверка права доступа.
	function get_item_permissions_check( $request ){
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Получает отдельный ресурс.
	 *
	 * @param WP_REST_Request $request Текущий запрос.
	 *
	 * @return array
	 */
	function get_item( $request ){
		$id = (int) $request['id'];
		$post = get_post( $id );

		if( ! $post )
			return array();

		return $this->prepare_item_for_response( $post, $request );
	}

	/**
	 * Собирает данные ресурса в соответствии со схемой ресурса.
	 *
	 * @param WP_Post         $post    Объект ресурса, из которого будут взяты оригинальные данные.
	 * @param WP_REST_Request $request Текущий запрос.
	 *
	 * @return array
	 */
	function prepare_item_for_response( $post, $request ){

		$post_data = [];

		$schema = $this->get_item_schema();

		// We are also renaming the fields to more understandable names.
		if ( isset( $schema['properties']['id'] ) )
			$post_data['id'] = (int) $post->ID;

		if ( isset( $schema['properties']['content'] ) )
			$post_data['content'] = apply_filters( 'the_content', $post->post_content, $post );

		return $post_data;
	}

	/**
	 * Подготавливает ответ отдельного ресурса для добавления его в коллекцию ресурсов.
	 *
	 * @param WP_REST_Response $response Response object.
	 *                                   
	 * @return array|mixed Response data, ready for insertion into collection data.
	 */
	function prepare_response_for_collection( $response ){

		if ( ! ( $response instanceof WP_REST_Response ) ){
			return $response;
		}

		$data = (array) $response->get_data();
		$server = rest_get_server();

		if ( method_exists( $server, 'get_compact_response_links' ) ){
			$links = call_user_func( [ $server, 'get_compact_response_links' ], $response );
		}
		else {
			$links = call_user_func( [ $server, 'get_response_links' ], $response );
		}

		if ( ! empty( $links ) ){
			$data['_links'] = $links;
		}

		return $data;
	}

	/**
	 * Возвращает схему ресурса.
	 *
	 * @return array
	 */
	function get_item_schema()
	{
		return $this->entity_class::get_schema();
	}

	## Устанавливает HTTP статус код для авторизации.
	function error_status_code(){
		return is_user_logged_in() ? 403 : 401;
	}

}