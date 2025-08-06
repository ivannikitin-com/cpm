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

class Plugin {
    /**
     * Единственный экземпляр данного класса
     */
    private static $instance = null;

    /**
     * Возвращает единственный экземпляр данного класса
     * @return Plugin
     */
    public static function get_instance() {
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
     * @var \CPM\REST\Manager
     */
    public $rest;
    
    /**
     * Модуль интерфейса
     * @var \CPM\Views\Manager
     */
    public $views;
    
    /**
     * Модуль расширений
     * @var \CPM\Extensions\Manager
     */
    public $extensions;


    /**
     * Конструктор
     */
    private function __construct() {
        // Загрузка базовых классов для модулей
        require_once( __DIR__ . '/core/manager_base.php' );

        // Менеджер ядра
        require_once( __DIR__ . '/core/manager.php' );
        $this->core = new \CPM\Core\Manager();

        // Хук ранней инициализации всех модулей
        add_action( 'init', array($this, 'init') );
    }

    /**
     * Инициализируем модули CPM по хуку init
     */
    public function init()
    {
        // Инициализация ядра
        $this->core->init();

    }


    /**
     * Метод выводит в лог сообщения с разным уровнем важности
     * @param string $message Сообщение
     * @param string $level Уровень важности
     * @return void
     */
    public function log( $message, $level = 'info' ) {
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