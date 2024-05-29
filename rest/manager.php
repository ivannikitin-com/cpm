<?php
/**
 * CPM REST API менеджер
 * Этот класс управляет инициализацией и контроллерами REST API
 */

namespace CPM\REST;

class Manager extends \CPM\Core\Manager
{
    /**
     * Конструктор менеджера
     */
    public function __construct() 
    {
        // Рабочая папка модуля
        $this->path = __DIR__ . '/';

        // Пространство имён модуля
        $this->namespace = __NAMESPACE__ . '\\';

        // Базовый конструктор
        parent::__construct();

         // Инициализация REST API
         add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Массив контроллеров
     */
    public $controllers = [];

    /**
     * Регистрация маршрутов
     */
    public function register_routes()
    {
        // Зарегистрируем контроллеры для классов с установленным статическим свойством $rest_api
        foreach ( \CPM\Plugin::get_instance()->get_classes() as $class ) {
            if ( isset( $class::$rest_api ) && $class::$rest_api ) {
                $this->controllers[ $class ] = new Controller( $class );
                $this->controllers[ $class ]->register_routes();
            }
        }
    }
}
