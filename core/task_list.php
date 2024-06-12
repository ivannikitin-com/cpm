<?php
/**
 * Класс Task_list
 * Список задач проекта
 * 
 * @author Ivan Nikitin
 * @package CPM
 * @version 3.0.1
 */

namespace CPM\Core;

class Task_list extends Project_item
{
   /**
    * Тип CPT
    */
    static public $CPT = 'cpm_task_list';

   /**
    * Свойства REST API
    */
    public static $rest_api = true;        // Этот объект доступен в REST API
    public static $rest_base = 'task_list';  // База сущности в URI
 
    /**
     * Путь до API
     * @return string
     */
    public static function get_api_path()
    {
       return '/task_list';
    }
 
    /**
     * Метод возвращает схему проекта для REST API
     * @static
     * @return array
     */ 
    static public function get_rest_schema()
    {
       $schema = parent::get_rest_schema();
       $schema[ 'properties' ][ 'coordinator' ] = array(
          'description' => __('Координатор проекта', CPM),
          'type'        => 'int'         
       );
       return $schema;       
    }

}