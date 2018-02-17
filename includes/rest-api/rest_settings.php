<?php
/**
 * Project manager pro - route class
 * @since 1.1
 */
class CPM_REST_Settings 
{
    /**
     * @var The single instance of the class
     * @since 1.1
     */
    protected static $_instance = null;

    /**
     * Main Cpmrp Instance
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
	 * Ключ авторизации текущего пользователя
     * @var string
     */
    public $key;

    /**
	 * Секрет авторизации текущего пользователя
     * @var string
     */
    public $secret;	
	
	
    /**
     * Инициализация параметов
     */	
    function __construct() 
	{
		// Инициализируем или генерируем новые данные для авторизации 
		
		
		
		
		// Страница настроек
		add_filter( 'cpm_settings_sections', array( $this, 'addSettingsPage' ) );
		add_filter( 'cpm_settings_fields', array( $this, 'addSettingsFields' ) );
    }
	
	/** 
	 * Добавляет страницу настроек
	 */
	public function addSettingsPage( $sections )
	{
		$sections[] = array(
                'id'    => get_class( $this ),
                'title' => __( 'REST API', 'cpm' )
            );

		return $sections;	
	}	
	
	/** 
	 * Добавляет поля настроек
	 */
	public function addSettingsFields( $settings_fields )
	{
		$settings_fields[ get_class( $this ) ] = array(
            array(
                'name'    => 'rest_key',
                'label'   => __( 'Ключ авторизации', 'cpm' ),
                'default' => '',
                'desc'    => __( 'Укажите свой ключ авторизации REST API или оставьте пустым и нажмите [Сохранить] - будет сгенерировано новое значение', 'cpm' )
            ),
            array(
                'name'    => 'rest_secret',
                'label'   => __( 'Секрет авторизации', 'cpm' ),
                'default' => '',
                'desc'    => __( 'Укажите свой секрет авторизации REST API или оставьте пустым и нажмите [Сохранить] - будет сгенерировано новое значение', 'cpm' )
            ),			
		);
		
		return $settings_fields;	
	}	
	
	/** 
	 * Генерирует новое значение ключей
	 */
	public function sanitize_callback( $value )
	{
		return sha1( microtime() . $salt );
	}	
	
	/** 
	 * Генерирует новое значение ключей
	 */
	public function generateNewValue( $salt )
	{
		return sha1( microtime() . $salt );
	}
	
	
}