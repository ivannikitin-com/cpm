<?php
/**
 * Класс Project
 * Проект CPM
 * 
 * @author Ivan Nikitin
 * @package CPM
 * @version 3.0.1
 */

 namespace CPM\Core;

 class Project extends Entity
 {
   /**
    * Тип сущности
    * Должно быть переопределено в наследнике
    */
   static public $CPT = 'cpm_project';

   /* -------------------- Инициализация ------------------- */
   public static function init()
   {
      // Регистрация CPT
      register_post_type( self::$CPT, array(
         'labels' => array(
               'name' => __( 'Проекты' , CPM ),
               'singular_name' => __( 'Проект' , CPM ),
               'add_new' => __( 'Добавить проект' , CPM ),
               'add_new_item' => __( 'Добавить проект' , CPM ),
               'edit_item' => __( 'Редактировать проект' , CPM ),
               'new_item' => __( 'Новый проект' , CPM ),
               'view_item' => __( 'Просмотреть проект' , CPM ),
               'search_items' => __( 'Найти проект' , CPM ),
               'not_found' => __( 'Проекты не найдены' , CPM ),
               'not_found_in_trash' => __( 'Проекты в корзине не найдены' , CPM )
         ),
         'public' => true,
         'show_ui' => true,
         'show_in_menu' => true,
         'supports' => array( 'title', 'editor', 'thumbnail' ),
         'menu_position' => 5,
         'menu_icon' => 'dashicons-admin-multisite',
         'taxonomies' => array( Category::$CPT ),
         'has_archive' => true
      ) );
   }

   /* --------------- Статичные методы проекта ----------------- */
   
   /**
    * Создание нового проекта
    * @param string  $title      Название
    * @param string  $content    Описание
    * @param string  $slug       Слаг
    * @param mixed   $data       Дополнительные данные
    * @return CPM\Core\Project
    */
   public static function create( $title, $content, $slug, $data )
   {
      return self::getEntity( parent::create( $title, $content, $slug, array(
         'post_type'     => self::$CPT,
         'meta_input'    => array()         
      ) ) );
   }



 }