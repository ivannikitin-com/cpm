<?php
/**
 * Основной класс CPM
 * @author Ivan Nikitin
 * @version 3.0.0
 * @package CPM
 * @subpackage plugin
 */
namespace CPM\Core;

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
     * Конструктор
     */
    private function __construct()
    {
        // Хуки и инициализация
        add_action( 'init', array($this, 'init') );
    }

    /**
     * Инициализация функция по хуку init
     */
    public function init()
    {
        // Инициализируем таксономии
        Category::init();
        
        // Инициализируем сущности
        Project::init();
    }
}   