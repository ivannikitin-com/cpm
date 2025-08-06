<?php
/**
 * Класс менеджера ядра CPM
 * Реализует загрузку и инициализацию всех классов ядра CPM
 *
 */
namespace CPM\Core;

class Manager extends ManagerBase {
    /**
     * Классы ядра и файлы с кодом
     * @static array $classes
     */
    private static $classes = array(
        'Item'          => __DIR__ . '/item.php',
        'Member'        => __DIR__ . '/member.php',
        'Entity'        => __DIR__ . '/entity.php',
        'EntityList'    => __DIR__ . '/entity_list.php',
        'EntityAccess'  => __DIR__ . '/entity_access.php'
    );


    /**
     * Конструктор
     * Загружает все классы ядра
     * Мы не используем автоматическую загрузку по ряду причин
     */
    public function __construct()
    {
        // Загрузка файлов, не указанных в списке классов
        require_once( __DIR__ . '/exceptions.php' );
        
        // Загрузка классов ядра
        foreach ( self::$classes as $class => $file ) {
            require_once $file;
        }
    }

    /**
     * Метод инициализации классов ядра
     */
    public function init() {
        // Инициализируем все классы по списку
        foreach ( self::$classes as $class => $file ) {
            $class = __NAMESPACE__ . '\\' . $class;
            $class::init();
        }        
    }
}