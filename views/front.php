<?php
/**
 * Основной плагин интерфейса CPM
 * Полностью отвечает за отображение CPM
 */

namespace CPM\Views;

class Front
{
   /* -------------------- Инициализация ------------------- */

   /**
    * Статичная инициализация системы интерфейса
    * Создание реального экземпляра выполняется только при первом
    * использовании интерфейса, например, при вызове шотркодов.
    * Это сделано для максимального ускорения работы сайта, когда CPM
    * не используется.
    */
    public static function init()
    {
        // Добавляем шорткоды
        add_shortcode( 'cpm' , array( self::class, 'cpm_shortcode' ) );
    }

    /* -------------------- Шорткоды ---------------------- */
    /**
     * Шорткод CPM
     * @param array $atts   атрибуты шорткода
     * @return string       содержимое шорткода
     */
    public static function cpm_shortcode( $atts = array() )
    {
        $output = '<div class="cpm_shortcode">';
        
        // Инициализируем фронт
        $front = self::getInstance();
        $output .= $front->render();

        $output .= '</div>';
        return $output;
    }


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
     * Текущее представление
     * @var CPM\Views\BaseView
     */
    private $view = null;


    /**
     * Отрисовка CPM
     */
    public function render()
    {
        $obj = CPM\Core\Manager::getInstance();

        return '!!!'; // var_export($obj, true);
    }
}