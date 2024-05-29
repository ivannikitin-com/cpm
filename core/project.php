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

   /* -------------------- REST API ------------------- */

   /**
    * Свойства REST API
    */
   public static $rest_api = true;        // Этот объект доступен в REST API
   public static $rest_base = 'project';  // База сущности в URI

   /**
    * Путь до API
    * @return string
    */
   public static function get_api_path()
   {
      return '/projects';
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
      $schema[ 'properties' ][ 'is_archive' ] = array(
         'description' => __('Архивный проект', CPM),
         'type'        => 'string'         
      ); 
      $schema[ 'properties' ][ 'is_active' ] = array(
      'description' => __('Активный проект', CPM),
      'type'        => 'string'         
   );
   return $schema;       
   }

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
         'is_active'     => '_project_active'      // Активный проект
      ));
   }
   
   /* -------------------- Запрос данных ------------------- */
   /**
    * SQL-запрос
    * @return string
    */
   protected static function get_sql()
   {
      global $wpdb;
      $cpt = self::$CPT;
      return <<< END_SQL
         SELECT
            ID,
            post_author,
            post_date,
            post_content,
            post_title,
            post_name,
            post_parent,
            menu_order,
            MAX(CASE WHEN pm.meta_key = 'team' THEN pm.meta_value ELSE NULL END) AS _team,
            MAX(CASE WHEN pm.meta_key = '_project_archive' THEN pm.meta_value ELSE NULL END) AS _project_archive,
            MAX(CASE WHEN pm.meta_key = '_project_active' THEN pm.meta_value ELSE NULL END) AS _project_active,
            MAX(CASE WHEN pm.meta_key = '_cpm_coordinator' THEN pm.meta_value ELSE NULL END) AS _cpm_coordinator
         FROM
            {$wpdb->posts} p
               INNER JOIN {$wpdb->postmeta} pm
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
      END_SQL;
   }

   /**
    * По умолчанию возвращаем только активные проекты
    * @static
    * @param array    $args     Параметры запроса
    */
   public static function readList( $args=array() ) {
      return parent::readList( array_merge( array( 
               '_project_archive' => 'no', 
               '_project_active'  => 'yes', 
            ) , $args ) );
   }

   /**
    * Конструктор
    */
   public function __construct( $args = array() )
   {
      // Родительский конструктор
      parent::__construct( $args );

      // Инициализация команды проекта для обратной совместимости
      if ( $this->team->is_empty() ) {
         // Возвращает список участников проекта, записанных в старом стиле
         foreach ( $this->get_old_style_members( $this->ID ) as $user ) {
            // Добавление участника в команду
            $this->team->add( new Member( $user[ 'user_id' ], $user[ 'user_role' ] ) );
         }
      }
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

   /* ------------ Обратная совместимость с CPM 1.x ------------------- */

    /**
     * Метод возвращает участников проекта, записанных в старом стиле
     * В CPM < 2.0.0 участники проекта хранятся в отдельной таблице `wp_cpm_user_role`
     * Решено только считывать их из этой таблицы, но при обновлении таблицу не перезаписывать,
     * а хранить участников проекта, как участников всех остальных сущностей -- в мета-данных. 
     * @param int $project_id ID проекта
     * @return array
     */
    private function get_old_style_members( $project_id ) {
      global $wpdb;

      // Проверяем наличие массива ролей старого стиля
      $cpm_user_roles = wp_cache_get( 'cpm_user_roles', 'cpm_project' );
      if ( empty( $cpm_user_roles ) ) {
          // Массив проект => array( user_id, user_role )
          $cpm_user_roles = array();

          // Запрос в БД
          $rows = $wpdb->get_results( "
              SELECT project_id, user_id, `role`
              FROM {$wpdb->prefix}cpm_user_role 
              ORDER BY id ASC
          ", ARRAY_A );

          // Формируем массив результатов
          foreach ( $rows as $row ) {
              $cpm_user_roles[ $row['project_id'] ][] = array(
                  'user_id' => $row['user_id'],
                  'user_role' => $row['role']
              );;
          }

          // Сохраняем в кэш
          wp_cache_set( 'cpm_user_roles', $cpm_user_roles, 'cpm_project' );
      }

      return isset( $cpm_user_roles[ $project_id ] ) ? $cpm_user_roles[ $project_id ] : array();
  }
}