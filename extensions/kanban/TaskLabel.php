<?php
/**
 * Метка задачи -- название секции канбан, в которой находится эта задача
 *
 * @package CPM/Extensions/Kanban
 * @version 2.0.0
 */
namespace CPM\Extensions\Kanban;
use \CPM\Extensions\Kanban as Kanban;
use \WP_Query as WP_Query;

class TaskLabel 
{
	/**
	 * \CPM\Extensions\Kanban $kanban	Ссылка на родительский объект, например, для доступа к настройкам
	 */
	private $kanban;
	
    /**
	 * Конструктор класса
	 * 
	 * @param Kanban $kanbanExtension	Ссылка на родительский объект, например, для доступа к настройкам
	 */
	public function __construct( Kanban $kanbanExtension )
	{
		$this->kanban = $kanbanExtension;
	}	
	
    /**
	 * Метод инициализирует расширение. В нем происходит установка всех хуков
	 */
	public function init()
	{
		// Вывод статуса задачи после контента TO-DO
		add_action( 'cpm_todo_content_after', array( $this, 'showLabel' ), 10, 4 );
		// Хуки для сброса кэшей
        add_action( 'wp_ajax_update_section_item', array( $this, 'update_section_item' ) );
        add_action( 'wp_ajax_delete_section', array( $this, 'update_section_item' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
	}
	
    /**
	 * Метод выводит метку задачи
	 * 
	 * @param int	$project_id		ID проекта
	 * @param int	$list_id		ID списка задач
	 * @param int	$task_id		ID задачи
	 * @param int	$single			Вывод отдной задачи
	 */
	public function showLabel( $project_id, $list_id, $task_id, $single )
	{
		// Находим ID секции
		$sectionId = apply_filters( 'cpm_kanban_tasklabel_section_id', $this->getSectionId( $project_id, $task_id ), 
									$project_id, $list_id, $task_id, $single );

		// Проверка, показывалась ли эта метка уже ранее для этого проекта
		// Необходимо, так как часто в CPM секция пишется дважды.
		
		// Находим азвание секции
		$sectionName = apply_filters( 'cpm_kanban_tasklabel_section_name', $this->getSectionName( $project_id, $sectionId ), 
									$project_id, $list_id, $task_id, $single, $sectionId );
		
		// Если имя пустое, ничего не выводим
		if ( empty( $sectionName) )
			return;
		
		// Формируем HTML
		$sectionLabel = apply_filters( 'cpm_kanban_tasklabel_label', 
									"<span class='cpm-kanban-task-label'>{$sectionName}</span>", 
									$project_id, $list_id, $task_id, $single, $sectionId, $sectionName );
		// Выводим HTML
		echo $sectionLabel;
	}
	
	/**
	 * Название кэшей
	 */
	const CACHE_SECTION_NAMES = 'section_names_';
	const CACHE_SECTION_IDS = 'section_ids_';	
	
    /**
	 * Метод название секции канбан
	 * @param int	$project_id		ID проекта, нужен для кэширования
	 * @param int	$section_id		ID секции	 
	 */
	private function getSectionName( $project_id, $section_id )
	{
		// Массив названий секций канбан
		$sectionNames = wp_cache_get( self::CACHE_SECTION_NAMES . $project_id, Kanban::CACHE_GROUP );
		if ( empty( $sectionNames ) )
			$sectionNames = array();
		
		// Если ID секции false, возвращаем false
		// Такое может быть, если метод getSectionId() не нашел секцию
		if ( $section_id == false )
			return false;
		
		// Проверим если имя есть в кэше, просто вернем его
		if ( array_key_exists( $section_id, $sectionNames) )
			return $sectionNames[ $section_id ]; 
		
		// Имени в кэше нет, найдем его и добавим в массив
		$sectionName = get_the_title( $section_id );
		$sectionNames[ $section_id ] = $sectionName;
		
		// Запишем массив название секций канбан в кэш
		wp_cache_set( self::CACHE_SECTION_NAMES . $project_id, $sectionNames, Kanban::CACHE_GROUP );
		
		// Вернем имя
		return $sectionName;
	}	
	
    /**
	 * Метод возвращает ID канбан секции по проекту и задаче
	 * @param int	$project_id		ID проекта
	 * @param int	$task_id		ID задачи	 
	 */
	private function getSectionId( $project_id, $task_id )
	{
		// Массив ID задачи => ID секции
		$sectionIds = wp_cache_get( self::CACHE_SECTION_IDS . $project_id, Kanban::CACHE_GROUP );
							  
		if ( empty( $sectionIds ) )
			$sectionIds = array();
		
		// Проверим если ID секции есть в кэше, просто вернем его
		if ( array_key_exists( $task_id, $sectionIds) )
			return $sectionIds[ $task_id ]; 
		
		// ID в кэше нет, построим массив всех ID секций проекта
		$query = new WP_Query( array(
			'post_type'		=> array( Kanban::CPT ),
			'post_status'	=> array( 'publish' ),
			'fields'		=> 'ids',
			'post_parent'	=> $project_id
		));
		$sections = $query->posts;
		
		// Читаем задачи из массива секций канбана
		foreach ( $sections as $sectionId )
		{
			$tasks = get_post_meta( $sectionId, '_tasks_id', true );
			if ( empty( $tasks ) )
				continue;
			// Заполним массив ID задач и секций
			foreach ( $tasks as $taskId )
			{
				$sectionIds[ $taskId ] = $sectionId;
			}
		}
		
		// Запишем массив название секций канбан в кэш
		wp_cache_set( self::CACHE_SECTION_IDS . $project_id, $sectionIds, Kanban::CACHE_GROUP );
		
		// Вернем имя
		return isset( $sectionIds[ $task_id ] ) ? $sectionIds[ $task_id ] : false;
	}
	
    /**
	 * Метод сбрасывает кэши при обновлении задач или проекта
	 * @param int	$project_id		ID проекта 
	 */
	private function flushCaches( $project_id )
	{
		wp_cache_delete( self::CACHE_SECTION_NAMES . $project_id, Kanban::CACHE_GROUP );
		wp_cache_delete( self::CACHE_SECTION_IDS . $project_id, Kanban::CACHE_GROUP );
	}
	
    /**
	 * Хуки для сброса кэшей при AJAX запросах плагина Канбан
	 * Читает номер секции и сбрасывает кэши проекта
	 */
	public function update_section_item()
	{
		$section_id = isset( $_POST[ 'section_id' ] ) ? intval( $_POST[ 'section_id' ] ) : 0;
		if ( empty( $section_id ) )
			return;
		
		// Проект, в котором эта Канбан секция
		$project_id = wp_get_post_parent_id( $section_id );
		
		// Если удалось определить проект
		if ( $project_id )
		{
			// Сбрасываем кэш
			$this->flushCaches( $project_id );
		}
			
	}
	
    /**
	 * Хук при обновлении любого поста
	 * https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
	 * 
	 * @param int	$post_id 	The post ID.
	 * @param post	$post 		The post object.
	 * @param bool	$update 	Whether this is an existing post being updated or not.	 
	 */
	public function save_post( $post_id, $post, $update )
	{
		// Если это Канбан секции, находим проект и сбрасываем кэш
		if ( get_post_type( $post_id ) == Kanban::CPT )
		{
			// Проект, в котором эта Канбан секция
			$project_id = wp_get_post_parent_id( $post_id );

			// Если удалось определить проект
			if ( $project_id )
			{
				// Сбрасываем кэш
				$this->flushCaches( $project_id );
			}		
		}
	}
}