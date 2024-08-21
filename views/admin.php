<?php
/**
 * Модуль интерфейса CPM в админ-панели WordPress
 * Полностью отвечает за отображение CPM в админ панели
 */

namespace CPM\Views;

class Admin
{
    /**
     * Единственный экземпляр данного класса
     */
    private static $instance = null;

    /**
     * Возвращает единственный экземпляр данного класса
     * @return Admin
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /* ------------------------------------------------------------------------- */

   /**
    * Статичная инициализация системы интерфейса
    * Создание реального экземпляра выполняется только в админке.
    * Это сделано для максимального ускорения работы сайта, когда CPM
    * не используется.
    */
    public static function init()
    {
        if (is_admin()) {
            self::getInstance();
        }
    }

    /**
     * Конструктор
     * Вызывается по событию init в админке
     */
    private function __construct()
    {
        // Хуки
        add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
        add_filter( 'option_page_capability_'.'my_page_slug', 'my_page_capability' );
    }

    /**
     * Иконка CPM в base64
     */
    public const ICON = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBVcGxvYWRlZCB0bzogU1ZHIFJlcG8sIHd3dy5zdmdyZXBvLmNvbSwgR2VuZXJhdG9yOiBTVkcgUmVwbyBNaXhlciBUb29scyAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8c3ZnIGZpbGw9IiMwMDAwMDAiIGhlaWdodD0iODAwcHgiIHdpZHRoPSI4MDBweCIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiANCgkgdmlld0JveD0iMCAwIDYwIDYwIiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxwYXRoIGQ9Ik01Myw0MVYyOUgzMVYxOC45MzFjMy45NC0wLjQ5NSw3LTMuODU5LDctNy45MzFjMC00LjQxMS0zLjU4OS04LTgtOHMtOCwzLjU4OS04LDhjMCw0LjA3MiwzLjA2LDcuNDM2LDcsNy45MzFWMjlIN3YxMkgwdjE2DQoJaDE2VjQxSDlWMzFoMjB2MTBoLTd2MTZoMTZWNDFoLTdWMzFoMjB2MTBoLTd2MTZoMTZWNDFINTN6Ii8+DQo8L3N2Zz4=';

    /**
     * Метод регистрирует страницу CPM в админке
     */
    public function register_admin_page() {
        add_menu_page( 
            __( 'CPM', CPM ),                   // page_title
            __( 'CPM', CPM ),                   // menu_title
            'edit_others_posts',                // capability
            \CPM_ADMIN_SLUG,                    // menu_slug
            array( $this, 'render_admin_page'), // Render function
            self::ICON,                         // icon
            4                                   // position
        );
    }

    /**
     * Отрисовка страницы админки
     */
    public function render_admin_page() {
        include \CPM_PATH . '/views/admin.view.php';
    }

}