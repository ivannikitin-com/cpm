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

    public static function getInstance()
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
}   