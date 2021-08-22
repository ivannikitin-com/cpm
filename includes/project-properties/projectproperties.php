<?php
/**
 * Класс реализует дополнительные свойства проектов CPM
 */
class CPM_ProjectProperties
{
    /**
     * @var The single instance of the class
     * @since 1.1
     */
    protected static $_instance = null;

    /**
     * Instance
     *
     * @since 0.1
     * @return CPM_REST_Settings - Main instance
     */
    public static function getInstance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
	
    /**
     * Конструктор класса, инициализация
     */	
    public function __construct() 
	{
		
		add_action( 'cpm_project_form', 				array( $this, 'showHeaderForm' ) );
		add_action( 'cpm_project_update', 				array( $this, 'saveHeaderForm' ), 10, 3 );
		add_action( 'cpm_project_after_description', 	array( $this, 'showProjectData' ) );
    }
	
	/**
	 * Вывод формы редактирования проекта
	 * @param CPM_Project | null	$project	Объект проекта или NULL если новый проект
	 */
	public function showHeaderForm( $project )
	{
		$coordinator = $this->getCoordinator( $project );
		
		
?>
	<h3>Свойства проекта</h3>	
	<div>
		<label>Координатор</label>
		 <?php wp_dropdown_users( array(
									'name'				=> self::META_COORDINATOR,					
									'selected' 			=> ( !empty( $coordinator ) ) ? $coordinator->ID : false,
									'role__in' 			=> array( 'employee', 'administrator' ),
									'show_option_none'	=> 'Не назначен',
									'class'				=> 'chosen-select',
								) ); ?>
		
	</div>


<?php		
	}
	
	/**
	 * Сохраняет данные формы редактирования проекта
	 * @param int	$project_id		ID Проекта (см. class/project.php)
	 * @param mixed	$data			Данные проекта (см. class/project.php)
	 * @param mixed	$posted			Судя по всему, данные POST
	 */
	public function saveHeaderForm( $project_id, $data, $posted )
	{
		if ( isset( $posted[self::META_COORDINATOR] ) )
		{
			$coordinatorId = (int) $posted[self::META_COORDINATOR];
			$project = CPM_Project::getInstance()->get( $project_id );
			$this->setCoordinator( $project, $coordinatorId );
		}
	}
	
	
	/**
	 * Показывает данные проекта в заголовке
	 * @param CPM_Project | null	$project	Объект проекта
	 */
	public function showProjectData( $project )
	{
		if ( empty( $project ) )
			return;
		
		$coordinator = $this->getCoordinator($project);		
		if ( $coordinator )
		{
			echo '<div class="cpm_projectproperties cpm_coordinator">Координатор: ',
				$coordinator->display_name,
				'</div>';			
		}


	}	

	
	/* -------------------------- Координатор -------------------------- */
	const META_COORDINATOR = '_cpm_coordinator';		// Мета-свойство сохранения координатора
	
    /**
     * @var mixed Массив кэша координаторов
     */
    private $coordinatorsCache;
	
	/**
	 * Возвращает координатора проекта
	 * @param CPM_Project | null	$project	Объект проекта или NULL если новый проект
	 * @return WP_User	Объект координатора проекта
	 */
	public function getCoordinator( $project )
	{	
		if ( ! $project )
			return null;
		
		// Читаем кэш
		if ( empty( $this->coordinatorsCache ) )
		{
			$this->coordinatorsCache = get_transient( self::META_COORDINATOR );
			if ( $this->coordinatorsCache === false )
				$this->coordinatorsCache = array();
		}
			 
		// Проверяем наличие проекта в кеше
		if ( array_key_exists( $project->ID, $this->coordinatorsCache ) )
		{
			// Проект есть в кэше, Возвращаем пользователя по ID из кэша
			return new WP_User( $this->coordinatorsCache[$project->ID] );
		}
			
		
		// Пользователя в кэше нет, Читаем свойство проекта и сохраняем в кэш
		$userId = (int) get_post_meta( $project->ID, self::META_COORDINATOR, true );
		$this->coordinatorsCache[$project->ID] = $userId;
		set_transient( self::META_COORDINATOR, $this->coordinatorsCache );
			
		// Координатор не назначен
		if ( empty ( $userId ) )	
			return null;
		
		// Возвращаем координатора
		return new WP_User( $userId );
	}
	
	/**
	 * Устанавливает координатора проекта
	 * @param CPM_Project | null	$project	Объект проекта или NULL если новый проект
	 * @param int					$userId		ID координатора проекта
	 * @return bool	Результат выполнения
	 */
	public function setCoordinator( $project, $userId )
	{	
		if ( ! $project )
			return false;

		// Читаем кэш
		if ( empty( $this->coordinatorsCache ) )
		{
			$this->coordinatorsCache = get_transient( self::META_COORDINATOR );
			if ( $this->coordinatorsCache === false )
				$this->coordinatorsCache = array();
		}		
		// Сохраняем в кэш
		$this->coordinatorsCache[$project->ID] = $userId;
		set_transient( self::META_COORDINATOR, $this->coordinatorsCache );
		
		return update_post_meta( $project->ID, self::META_COORDINATOR, $userId );
	}	
	
}