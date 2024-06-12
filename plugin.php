<?php
/**
 * Основной класс CPM
 * Инициализирует модули CPM
 * Реализован как Singleton чтобы иметь доступ из любого кода
 * 
 * @author Ivan Nikitin
 * @version 3.0.0
 * @package CPM
 */
namespace CPM;

class Plugin
{
    /**
     * Единственный экземпляр данного класса
     */
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Модуль ядра
     * @var \CPM\Core\Manager
     */
    public $core;
    
    /**
     * Модуль REST API
     * @var \CPM\Core\Manager
     */
    public $rest;
    
    /**
     * Модуль интерфейса
     * @var \CPM\Core\Manager
     */
    public $views;
    
    /**
     * Модуль расширений
     * @var \CPM\Core\Manager
     */
    public $extensions;


    /**
     * Конструктор
     */
    private function __construct()
    {
        // Файлы модулей
        require_once __DIR__ . '/core/manager.php';
        require_once __DIR__ . '/rest/manager.php';
        require_once __DIR__ . '/views/manager.php';
        require_once __DIR__ . '/extensions/manager.php';

        // Хук ранней инициализации модулей
        add_action( 'init', array($this, 'init') );
    }

    /**
     * Инициализируем подсистемы CPM
     */
    public function init()
    {
        // Ядро
        $this->core = new \CPM\Core\Manager();

        // REST
        $this->rest = new \CPM\REST\Manager();
        
        // Интерфейс
        $this->views = new \CPM\Views\Manager();
        
        // Расширения
        $this->extensions = new \CPM\Extensions\Manager();
    }

    /**
     * Полный список всех классов CPM
     */
    public function get_classes() 
    {
        return array_merge( 
            $this->core->get_classes(), 
            $this->rest->get_classes(), 
            $this->views->get_classes(), 
            $this->extensions->get_classes() 
        );
    }

    /**
     * Метод выводит в лог сообщения с разным уровнем важности
     * @param string $message Сообщение
     * @param string $level Уровень важности
     * @return void
     */
    public function log( $message, $level = 'info' )
    {
        // Выводим только в режиме отладки
        if ( ! defined('WP_DEBUG') || ! WP_DEBUG ) {
            return;
        }

        // Уровни отладки
        $debug_levels = ( defined('WP_DEBUG_LOG_LEVELS') && WP_DEBUG_LOG_LEVELS ) ? 
            explode( ',', WP_DEBUG_LOG_LEVELS ) :
            array( 'debug', 'SQL', 'query', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency' );
        if ( ! in_array( $level, $debug_levels ) ) {
            return;
        }

        // Вывод в лог может быть переопределен хуком
        $message = apply_filters( 'cpm_log', $message, $level );
        if ( ! $message ) {
            return;
        }

        // Вывод в лог
        error_log( 'CPM ' . $level . ': ' . $message );
    }
}   