<?php
/**
 * Класс REST контроллера для запроса задач
 * https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#examples
 */
class CPM_Task_REST_Controller extends WP_REST_Controller
{
	public $namespace;
	public $resource_name;
	
	/**
	 * Конструктор
	 */
	public function __construct()
	{
		$this->namespace = '/cpm/v1';
		$this->resource_name = 'task';
			
	}
	
	/**
	 * Регистрируем маршруты и методы		 
	 */	
    public function register_routes() 
	{		
		// Методы для запросов списка записей очета и создания новой записи
        register_rest_route( $this->namespace, '/' . $this->resource_name, array(
            array(
                'methods'   			=> WP_REST_Server::READABLE,	// GET
                'callback'  			=> array( $this, 'get_items' ),
                'permission_callback'	=> array( $this, 'get_items_permissions_check' ),
            ),			
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );
		
		// Метод запроса отдельной записи
        register_rest_route( $this->namespace, '/' . $this->resource_name . '/(?P<id>[\d]+)', array(        
            array(
                'methods'   			=> WP_REST_Server::READABLE,		// GET
                'callback'  			=> array( $this, 'get_item' ),
                'permission_callback'	=> array( $this, 'get_item_permissions_check' ),
            ),				
            // Register our schema callback.
            'schema' => array( $this, 'get_item_schema' ),
        ) );
		
		// Запрос схемы через GET
		register_rest_route( $this->namespace, '/' . $this->resource_name . '/schema', array(
		  'methods'         => WP_REST_Server::READABLE,
		  'callback'        => array( $this, 'get_item_schema' ),
		) );		
		
    }	
	
    /**
     * Возвращает схему данных
	 * https://spacetelescope.github.io/understanding-json-schema/index.html
     */
    public function get_item_schema() 
	{
        $schema = array(
            // This tells the spec of JSON Schema we are using which is draft 4.
            '$schema'              => 'http://json-schema.org/draft-04/schema#',
            // The title property marks the identity of the resource.
            'title'                => 'task',
            'type'                 => 'object',
            // In JSON Schema you can specify object properties in the properties attribute.
            'properties'           => array(
                'id' => array(
                    'description'  => 'ID задачи',
                    'type'         => 'integer',
                    'readonly'     => true,
                ),
                'category' => array(
                    'description'  => 'Категория проекта',
                    'type'         => 'string',
                    'readonly'     => true,					
                ),
                'project' => array(
                    'description'  => 'Проект',
                    'type'         => 'string',	
                    'readonly'     => true,							
                ),
                'list' => array(
                    'description'  => 'Список задач',
                    'type'         => 'string',
                    'readonly'     => true,							
                ),
                'task' => array(
                    'description'  => 'Задача',
                    'type'         => 'string',
                    'readonly'     => true,						
                ),
	            'appointedTo' => array(
                    'description'  => 'Список исполнителей',
                    'type'         => 'string',
                    'readonly'     => true,						
                ),
	            'startDate' => array(
                    'description'  => 'Дата начала',
                    'type'         => 'string',
					'format'	   => 'date-time',
                    'readonly'     => true,
                ),	            
				'endDate' => array(
                    'description'  => 'Дата завершения',
                    'type'         => 'string',
					'format'	   => 'date-time',
                    'readonly'     => true,						
                ),
				'complete' => array(
                    'description'  => 'Завершено',
                    'type'         => 'boolean',
                    'readonly'     => true,						
                ),
            ),
        ); 
        return $schema;
    }

// --------------------------------------- Аутентификация пользователя ---------------------------------------
	/**
	 * Проверяет и пытается провести аутентификацию через пароли приложений
	 */
	public function checkAuthentification()
	{

		if ( ! is_user_logged_in() && isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) )
		{
			$user = wp_authenticate( $_SERVER['PHP_AUTH_USER'] , $_SERVER['PHP_AUTH_PW']  );
			
			// Проверка ошибок
			if ( ! is_wp_error( $user ) ) 
			{
				 wp_set_current_user( $user->ID, $user->user_login );
			}			
		}
	}
	
	
// --------------------------------------- Подготовка данных ---------------------------------------
    /**
     * Подготавливает ответ одной задачи в соотвествии со схемой
     *
     * @param int	$postId		ID записи
     */
    public function prepare_item_for_response( $postId, $request ) 
	{
		$post_data = array();
		$task = CPM_Task::getInstance()->get_task( $postId );
		if ( $task ) 	
		{
		
			$list = get_post( $task->post_parent );
			$project = get_post( $list->post_parent );
			$projectCategories = implode(', ', wp_get_post_terms( $project->ID, 'cpm_project_category', array('fields' => 'names' ) ) );
			
			$taskUsers = array();
			foreach ( $task->assigned_to as $taskUserId )
			{
				$taskUser = get_user_by( 'id', $taskUserId );
				$taskUsers[] = $taskUser->display_name;
			}
			
			$startDate = $task->start_date;
			if ( empty( $startDate ) )
			{
				$startDate = $task->post_date;
			}

			$post_data['id'] 	= $task->ID;		
			$post_data['category'] 	= $projectCategories;		
			$post_data['project'] 	= $project->post_title;		
			$post_data['list'] 	= $list->post_title;		
			$post_data['task'] 	= $task->ID;		
			$post_data['appointedTo'] 	= implode( ', ', $taskUsers );		
			$post_data['startDate'] 	= $startDate;		
			$post_data['endDate'] 	= $task->due_date;		
			$post_data['complete'] 	= (bool) $task->completed;		
		}

        return rest_ensure_response( $post_data );
    }

// --------------------------------------- Запрос одной задачи ---------------------------------------
	
   /**
     * Проверка прав на доступ к отдельной записи
     *
     * @param WP_REST_Request $request Текущий запрос
     */
    public function get_item_permissions_check( $request ) 
	{
		$this->checkAuthentification();
		
		// Проверка авторизации пользователя
		if ( ! is_user_logged_in() )
			return new WP_Error( 'cpm_rest_unauthorized', 'Вы не авторизованы!', array( 'status' => '401' ) );
		
		$hasPermission = cpm_can_manage_projects();
		
		// Проверка прав на доступ к отчетам
		if ( ! $hasPermission ) 
			return new WP_Error( 'cpm_rest_forbidden', 'У вас нет прав на доступ к данным!', array( 'status' => '403' ) );
		
        return $hasPermission;
    }	

    /**
     * Получаем отдельную запись
     *
     * @param WP_REST_Request $request Current request.
     */
    public function get_item( $request ) 
	{
 		$id = (int) $request['id'];
        $response = $this->prepare_item_for_response( $id, $request );
        return $response;
    }
		
// --------------------------------------- Запрос задач ---------------------------------------	
    /**
     * Проверка прав на доступ к списку задач
     *
     * @param WP_REST_Request $request Текущий запрос
     */
    public function get_items_permissions_check( $request ) 
	{        
		$this->checkAuthentification();
		
		// Проверка авторизации пользователя
		if ( ! is_user_logged_in() )
			return new WP_Error( 'cpm_rest_unauthorized', 'Вы не авторизованы!', array( 'status' => '401' ) );
		
		$hasPermission = cpm_can_manage_projects();
		
		// Проверка прав на доступ к отчетам
		if ( ! $hasPermission ) 
			return new WP_Error( 'cpm_rest_forbidden', 'У вас нет прав на доступ к данным!', array( 'status' => '403' ) );
		
        return $hasPermission;
    }	
	
    /**
     * Получаем список записей
     * @param WP_REST_Request $request Объект текущего запроса
     */
    public function get_items( $request ) 
	{		
		// Параметры запроса
		$categoryName = ( isset ( $request['category'] ) ) ? $request['category'] : '';
		$projectName = ( isset ( $request['project'] ) ) ? $request['project'] : '';
		$user = ( isset ( $request['user'] ) ) ? $request['user'] : '';
		$startDate = ( isset ( $request['date_start'] ) ) ? $request['startDate'] : '';
		$endDate = ( isset ( $request['date_start'] ) ) ? $request['endDate'] : '';
		$completed = ( isset ( $request['completed'] ) ) ? $request['completed'] : '';
		
		// ----------  Находим активные проекты
		$queryArgs = array(
			'fields' => 'ids',
			'post_type' => 'cpm_project',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => '_project_active',
					'value'   => 'yes',
					'compare' => '='
					),
				),
			);

		// Если указана категория, tax_query
		if ( ! empty( $categoryName ) )
		{
			$queryArgs['tax_query'] = array(
				array(
					'taxonomy' => 'cpm_project_category',
					'field'    => 'name',
					'terms'    => explode(',', $categoryName),
					)
				);
		}	
		
		// Если проект не указан, запрашиваем все проекты, возможно по категории
		if ( ! empty( $projectName ) )
		{
			$queryArgs['s'] = $projectName;		// Трюк! Мы ищем с помощью поиска!!!
		}
		
		// Запрос проектов 
		$query = new WP_Query( $queryArgs );
		$projectIds = $query->posts;
		
		// Если проекты не найдены, возвращаем пусто
		if ( count( $projectIds ) == 0 )
			return rest_ensure_response( array() );
		
		// Запрос списов задач в выбранных проектах
		$query = new WP_Query( array(
			'fields' => 'ids',
			'post_type' => 'cpm_task_list',
			'post_parent__in' => $projectIds,
		) );
		$listIds = $query->posts;
		
		// Запрос задач в указанных списках
		$queryArgs = array(
			'fields' => 'ids',
			'post_type' => 'cpm_task',
			'post_parent__in' => $listIds,
			'meta_query' => array(
							'relation' => 'AND'
							)
			);
		
		// Задачи одного пользователя
		if ( ! empty( $user ) )
		{
			$userInfo = get_user_by('login', $user );
			if ( $userInfo )
			{		
				$queryArgs['meta_query'][] = array(
												'key' => '_assigned',
												'value' => $userInfo->ID
											);
			}
			else
			{
				// Такого пользователя нет, возвращаем пустой массив
				return rest_ensure_response( array() );
			}

		}
		
		// Стаус завершения
		if ( ! empty( $completed ) )
		{
			$queryArgs['meta_query'][] = array(
											'key' => '_completed',
											'value' => ( $completed ) ? 1 : 0,
										);
		}
		
		$query = new WP_Query( $queryArgs );
		$taskIds = $query->posts;
		
		// Данные для ответа
		$tasks = array();
		
		// Наполяем ответ задачами
		foreach( $taskIds as $taskId )
		{
			$taskData = $this->prepare_item_for_response( $taskId, $request );
			$tasks[] = $this->prepare_response_for_collection( $taskData );
		}
		
		return rest_ensure_response( $tasks );
    }
}
 