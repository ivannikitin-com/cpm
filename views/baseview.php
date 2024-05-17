<?php
/**
 * Базовый класс представления
 * 
 * @package CPM
 * @author Ivan Nikitin
 *  
 */

namespace CPM\Views;

class BaseView
{
    /**
     * Файл шаблона
     * @var string
     */
    public $template = '';

    /**
     * Объект отрисовки
     * @var \CPM\Core\Entity
     */
    public $entity = null;    

    /**
     * Конструктор
     * 
     * @return void
     */
    public function __construct( $entity )
    {
 
    }

    /**
     * Метод отрисовывает объект по шаблону  
     */
    public function render()
    {
        $output = var_export( \CPM\Core\Manager::get_instance(), true );
        return $output;
    }
}