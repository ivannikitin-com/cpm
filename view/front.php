<?php
/**
 * Основной плагин интерфейса CPM
 * Полностью отвечает за отображение CPM
 */

namespace CPM\View;

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
     * Конструктор
     */
    private function __construct()
    {
        
    }

    /**
     * Отрисовка CPM
     */
    public function render()
    {
        $output = '<pre>';

        $projects = \CPM\Core\Project::readList();
        $count = 0;

        foreach ($projects as $project) {
            $count++;
            if ($count > 5) break;
            $output .= var_export($project, true) . PHP_EOL . PHP_EOL;
        }

        $output .= '</pre>';
        return $output;
    }
}