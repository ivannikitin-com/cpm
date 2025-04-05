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

   /* -------------------- Инициализация ------------------- */
   public static function init()
   {
      // Регистрация CPT
      register_post_type( self::$CPT, array(
         'labels' => array(
               'name' => __( 'Списки задач' , CPM ),
               'singular_name' => __( 'Список задач' , CPM ),
               'add_new' => __( 'Добавить список задач' , CPM ),
               'add_new_item' => __( 'Добавить список задач' , CPM ),
               'edit_item' => __( 'Редактировать список задач' , CPM ),
               'new_item' => __( 'Новый список задач' , CPM ),
               'view_item' => __( 'Просмотреть список задач' , CPM ),
               'search_items' => __( 'Найти список задач' , CPM ),
               'not_found' => __( 'Списки задач не найдены' , CPM ),
               'not_found_in_trash' => __( 'Списки задач в корзине не найдены' , CPM )
         ),
         'public' => true,
         'show_ui' => true,
         'show_in_menu' => \CPM_ADMIN_SLUG,
         'supports' => array( 'title', 'editor', 'thumbnail' ),
         'menu_position' => 5,
         //'menu_icon' => 'dashicons-admin-multisite',
         'taxonomies' => array( Category::$CPT ),
         'has_archive' => true
      ) );

      // Хуки
      add_filter( 'manage_edit-' . self::$CPT . '_columns', array( __CLASS__, 'get_post_columns' ), 25 ); // Добавление колонок в админке
      add_action( 'manage_posts_custom_column', array( __CLASS__, 'show_post_columns' ), 25, 2 );         // Значения колонок в админке
   }

   /**
    * Список колонок в админке
    * @static
    * @param array $columns      Список существующих колонок
    */
   public static function get_post_columns( $columns) {

      $new_columns = array();
      $new_columns[ 'project' ] = __('Проект', CPM);

      $columns = array_slice( $columns, 0, 2, true ) + $new_columns + array_slice( $columns, 2, NULL, true );
      return $columns;
   }

   /**
    * Значения колонок в админке
    * @static
    * @param array $column_name  Название колонки
    * @param int   $post_id      ID записи
    */
   public static function show_post_columns( $column_name, $post_id ) {
      switch ( $column_name ) {
         case 'project':
            echo get_post_meta( $post_id, '_cpm_coordinator', true );
            break;
         case 'is_active':
            echo get_post_meta( $post_id, '_project_active', true );
            break;
         case 'is_archive':
            echo get_post_meta( $post_id, '_project_archive', true );
            break;
      }
   }

}