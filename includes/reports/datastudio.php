<?php
/**
 * Класс реализует отображение отчетов Google Data Studio в проектах CPM
 */
class CPM_DataStudio
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
	 * @const Параметр в URL владки отчета
	 */
	const TAB_PARAM = 'datastudio_';
	
	/**
	 * @const Параметры модуля в мета-поле проекта
	 */
	const META_FIELD = '_datastudio_reports';
	
	/**
	 * Массив с данными отчета
	 */
	public $reports;
	
	/**
	 * ID проекта, для которого параметры загружены
	 */
	private $projectIdParamsLoaded;
	
	
	
    /**
     * Конструктор класса, инициализация
     */	
    function __construct() 
	{
		// Отобюражение вкладок в проекте
		add_filter( 'cpm_project_nav_links', array( $this, 'addTabs' ), 10, 2 );
		add_filter( 'cpm_tab_file', array( $this, 'getTabTemplate' ), 10, 5 );
		add_action( 'cpmf_project_tab', array( $this, 'getTabTemplateFrontend' ), 10, 3 );
		add_action( 'cpm_project_settings', array( $this, 'showProjectSettings' ) );
		add_action( 'cpm_project_settings_update', array( $this, 'updateProjectSettings' ) );
    }
	
	/**
	 * Загружает данные отчетов проекта в поля объяета
	 * @param int	$projectId	ID проекта	 
	 */
	public function loadParams( $projectId )
	{
		// Если параметры уже загружены, ничего не делаем.
		if ( $this->projectIdParamsLoaded == $projectId )
			return;
		
		$this->reports = array();
		$metaString = get_post_meta( $projectId, self::META_FIELD, true );
		if ( ! empty( $metaString ) )
		{
			$this->reports = json_decode( $metaString, true );
			if ( json_last_error() !== JSON_ERROR_NONE )
				$this->reports = array();
			
			$this->projectIdParamsLoaded = $projectId;
		}
	}
	
    /**
     * Отображение вкладок отчетов
	 * @param mixed	$links		Массив ссылок
	 * @param int	$projectId	ID проекта
	 * @return mixed массив ссылок
     */	
    function addTabs( $links, $projectId )  
	{
		$this->loadParams( $projectId );
		
		foreach ( $this->reports as $no => $report )
		{
			$links[ $report['title'] ] = array(
				'url' => $this->getTabURL( $projectId, $no ), 
				'count' => $report['subtitle'], 
				'class' => 'datastudio overview cpm-sm-col-12' );
		}
		
		return $links;
    }
	
    /**
     * Возвращает URL на вкладку отчета
	 * @param int	$projectId	ID проекта
	 * @return string ссылка
     */		
    function getTabURL( $projectId, $reportId ) {
		
		
		$tab = self::TAB_PARAM . $reportId;
		
        if ( is_admin() ) 
		{
            $url = sprintf( '%s?page=cpm_projects&tab=%s&action=index&pid=%d', 
						   admin_url( 'admin.php' ), $tab, $projectId );
        } 
		else 
		{
            $page_id = cpm_get_option('project');
            $url = add_query_arg( array(
                'project_id' => $projectId,
                'tab'        => $tab,
                'action'     => 'index'
            ), get_permalink( $page_id ) );
        }

        return apply_filters( 'cpm_datastudio_tab_url', $url, $projectId, $reportId );
    }
	
	/**
	 * Вовзращает массив с текущим (по URL) отчетом
	 */
	public function getReport()
	{
		if ( isset( $_GET['tab'] ) )
		{
			$tab = sanitize_text_field( $_GET['tab'] );
		
			if ( preg_match('/^' . self::TAB_PARAM . '/', $tab ) ) 
			{
				$parts = explode( '_', $tab );
				
				$this->loadParams( $this->getProjectId() );
				if ( isset( $this->reports[ $parts[1] ] ) )
					return $this->reports[ $parts[1] ];
			}			
		}
		return array();
	}
	
	
	
    /**
     * Возвращает файл шаблона
	 * @param int		$projectId	ID проекта
	 * @param int		$page		страница
	 * @param string	$tab		имя вкладки
	 * @param string	$action		действие
	 * @return string файл шаблона
     */		
    public function getTabTemplate( $file, $projectId, $page, $tab, $action  ) 
	{
        if ( preg_match('/^' . self::TAB_PARAM . '/', $tab ) ) 
		{
            $file = CPM_PATH . '/views/datastudio/index.php';
        }

        return $file;
    }	
	
    /**
     * Возвращает файл шаблона для fontend
	 * @param int		$projectId	ID проекта
	 * @param string	$tab		имя вкладки
	 * @param string	$action		действие
	 * @return string файл шаблона
     */		
    function getTabTemplateFrontend( $projectId, $tab, $action  ) 
	{
		$template = $this->getTabTemplate( '', $projectId, 0, $tab, $action);
		if ( ! empty( $template ) )
			include $template;
	}
	
	
	/**
	 * Возвращает ID ткущего проекта
	 */
	public function getProjectId()
	{
		$projectId = 0;
		
		if ( isset( $_GET[ 'pid' ] ) ) 
			$projectId = sanitize_text_field( $_GET[ 'pid' ] );
		
		if ( isset( $_GET[ 'project_id' ] ) ) 
			$projectId = sanitize_text_field( $_GET[ 'project_id' ] );

		return $projectId;
	}
	
	/**
	 * Сохраняет настройки отчетов в проекте
	 */
	public function updateProjectSettings()
	{
		$projectId = $this->getProjectId();
		$this->loadParams( $projectId );
		
		// Добавление строки настроек
		if ( isset( $_POST['datastudio_report_add'] ) )
		{
			$title = ( isset( $_POST['datastudio_title'] ) ) ? sanitize_text_field( $_POST['datastudio_title'] ) : '';
			$subtitle = ( isset( $_POST['datastudio_subtitle'] ) ) ? sanitize_text_field( $_POST['datastudio_subtitle'] ) : '';
			$code = ( isset( $_POST['datastudio_code'] ) ) ? sanitize_text_field( $_POST['datastudio_code'] ) : '';
			$width = ( isset( $_POST['datastudio_width'] ) ) ? sanitize_text_field( $_POST['datastudio_width'] ) : '';
			$height = ( isset( $_POST['datastudio_height'] ) ) ? sanitize_text_field( $_POST['datastudio_height'] ) : '';
			
			if ( $title && $code )
			{
				$this->reports[] = array(
					'title' => $title,
					'subtitle' => $subtitle,
					'code' => $code,
					'width' => $width,
					'height' => $height,
				);
				
				$metaString = json_encode( $this->reports, JSON_UNESCAPED_UNICODE );
				update_post_meta( $projectId, self::META_FIELD, $metaString );
				
				// JavaScript redirect
				echo "<script>window.location.replace(window.location.href+'&saved=1');</script>";
				
			}
		}
		
		// Удаление строки настроек 
		if ( isset( $_POST['datastudio_report_delete'] ) )
		{
			$no = (int) sanitize_text_field( $_POST['datastudio_report_delete'] );
			unset( $this->reports[$no] );
			$metaString = json_encode( $this->reports, JSON_UNESCAPED_UNICODE );
			update_post_meta( $projectId, self::META_FIELD, $metaString );
			
			// JavaScript redirect
			echo "<script>window.location.replace(window.location.href+'&saved=1');</script>";			
		}
		
		// Сбрасываем флаг загрузки параметров
		$this->projectIdParamsLoaded = 0;
	}
	
	/**
	 * Показывает параметры Data Studio в проекте
	 */
	public function showProjectSettings()
	{
		$projectId = $this->getProjectId();	
		$this->loadParams( $projectId );
	?>
	<h3>Отчеты Google Data Studio</h3>
	<table style="max-width:100%">
		<thead>
			<tr>
				<td>#</td>
				<td>Название владки</td>
				<td>Пояснение</td>
				<td>URL для встраивания</td>
				<td>Ширина</td>
				<td>Высота</td>
				<td>&nbsp;</td>
			</tr>
		</thead>	
		<tbody>
		<?php foreach ( $this->reports as $no => $report ): ?>
			<tr>
				<td><?php echo $no ?></td>
				<td><?php echo $report['title'] ?></td>
				<td><?php echo $report['subtitle'] ?></td>
				<td><div style="width:300px;overflow:hidden;"><?php echo $report['code'] ?></div></td>
				<td><?php echo $report['width'] ?></td>
				<td><?php echo $report['height'] ?></td>
				<td><button type="submit" name="datastudio_report_delete" value="<?php echo $no ?>" >X</button></td>
			</tr>
		<?php endforeach ?>			
		</tbody>
		<tbody>
			<tr>
				<td>*</td>
				<td><input type="text" name="datastudio_title"></td>
				<td><input type="text" name="datastudio_subtitle"></td>
				<td><input type="text" name="datastudio_code"></td>
				<td><input type="text" name="datastudio_width"></td>
				<td><input type="text" name="datastudio_height"></td>
				<td><button type="submit" name="datastudio_report_add" value="1">Добавить</button></td>
			</tr>		
		</tbody>		
	</table>
	<?php

	}
	
}