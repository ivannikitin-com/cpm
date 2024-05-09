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

   /* -------------------- Мета-данные ------------------- */
   /**
    * Дополнительные свойства проекта 
    * Метод возвращает массив соответствия имен мета-полей
    * @return array
    */
   protected static function get_meta_fields()
   {
      return array_merge( parent::get_meta_fields(), array(
         'coordinator'   => '_cpm_coordinator',    // Координатор проекта
         'is_archive'    => '_project_archive',    // Архивный проект
         'is_active'     => '_project_active',     // Активный проект
         'meta_settings' => '_settings'            // Настройка проекта (для обратной совместимости)
      ))
   }
   
   /* -------------------- Запрос данных ------------------- */
   /**
    * SQL-запрос
    * @return string
    */
   protected static function get_sql()
   {
      global $wpdb;
      $cpt = self::CPT;
      return <<<SQL 
SELECT
   ID,
	post_author,
	post_date,
	post_content,
	post_title,
	post_name,
	post_parent,
	menu_order,
	MAX(CASE WHEN pm.meta_key = 'team' THEN pm.meta_value ELSE NULL END) AS team,
	MAX(CASE WHEN pm.meta_key = '_project_archive' THEN pm.meta_value ELSE NULL END) AS _project_archive,
	MAX(CASE WHEN pm.meta_key = '_project_active' THEN pm.meta_value ELSE NULL END) AS _project_active,
	MAX(CASE WHEN pm.meta_key = '_settings' THEN pm.meta_value ELSE NULL END) AS _settings,
	MAX(CASE WHEN pm.meta_key = '_cpm_coordinator' THEN pm.meta_value ELSE NULL END) AS _cpm_coordinator
FROM
    {$wpdb->posts} p
        INNER JOIN {$wpdb->postsmeta} pm
            ON p.ID = pm.post_id
WHERE
    post_type = '{$cpt}'
GROUP BY
    ID
HAVING
	TRUE
   -- EXTRA_WHERE --
ORDER BY
	menu_order DESC,
	post_title ASC    
SQL;
   }

   /**
    * По умолчанию возвращаем только активные проекты
    * @static
    * @param array    $args     Параметры запроса
    */
   public static function readList( $args ) {
      return parent::readList( array_merge( array( 
               '_project_archive' => 'no', 
               '_project_active'  => 'yes', 
            ) , $args ) );
   }

   /**
    * Старые настройки проекта
    * @var array
    */
   public $settings = null;

   /**
    * Конструктор
    */
   public function __construct( $args = array() )
   {
      // Родительский конструктор
      parent::__construct( $args );

      // Расшифровываем старые настройки проекта
      if ( isset( $this->meta_settings ) ) {
         $this->settings = unserialize( $this->meta_settings );
      }
      else {
         $this->settings = array();
      }

      // Формируем команду по старым настройкам проекта
      // TODO: Сделать формирование команды по старым настройкам проекта
   }

   /**
    * Обновление проекта в БД
    */
   public function update()
   {
      // Сохранение параметров в старов виде
      // TODO: сохранять в старом виде
      // $this->meta_settings = ...;

      // Обновление проекта
      return parent::update();
   }

}