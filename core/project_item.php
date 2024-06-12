<?php
/**
 * Класс Project_item
 * Реализует общие методы элементов проекта: списки задач и т.п.
 * Проект CPM
 * 
 * @author Ivan Nikitin
 * @package CPM
 * @version 3.0.1
 */

 namespace CPM\Core;

class Project_item extends Entity
{
    /**
    * Свойства REST API
    */
    public static $rest_api = false;        // Этот объект НЕ доступен в REST API

    /**
     * Проект этого объекта
     * @var Project
     */
    public $project = null;

    /**
     * Конструктор
     * @param mixed    $args     Массив полей объекта, принимает массив полей полученных из запроса БД
     */
    public function __construct( $args )
    {
        parent::__construct();

        // Если в списке полей есть поле project, то создаем объект проекта
        if ( isset( $args['project'] ) ) {
            $this->project = new Project( $args['project'] );
        }
    }

    /**
     * Метод возвращает схему проекта для REST API
     * @static
     * @return array
     */ 
    static public function get_rest_schema()
    {
       $schema = parent::get_rest_schema();
       $schema[ 'properties' ][ 'project_id' ] = array(
          'description' => __('ID проекта', CPM),
          'type'        => 'int'         
       );
       return $schema;       
    }

    /**
     * Статический метод возвращает объект сущности для REST API
     * в соответствии с полями схемы
     * @static
     * @param Entity $entity Объект сущности
     * @return array
     */
    static public function get_rest_item( $item )
    {
        return array_merge( parent::get_rest_item( $item ), array( 
            'project_id' => $item->project->id 
        ) );
    }


    /* -------------- Права доступа к объектам сущности  ------------ */

    /**
     * Метод возвращает true если пользователю разрешена эта операция
     * @param string    $operation    Операция с объектом
     * @param string    $entity_class Класс сущности
     * @param int       $user_id      ID пользователя
     * @return bool
     */
    public function user_can( $operation, $entity_class = '', $user_id = 0 )
    {
        // Определен ли проект этого объекта?
        if ( ! isset( $this->project ) ) {
            \CPM\Plugin::get_instance()->log( self::class . '::user_can() Не определен проект для сущности ' . static::get_class_name(), 'error' );
            return false;
        }

        // Права на элементы проекта определяются на уровне самого проекта
        return $this->project->user_can( $operation, static::get_class_name(), $user_id );
    }    
}
