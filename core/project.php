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
    * Тип CPT
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
         'show_in_menu' => \CPM_ADMIN_SLUG,
         'supports' => array( 'title', 'editor', 'thumbnail' ),
         'menu_position' => 5,
         //'menu_icon' => 'dashicons-admin-multisite',
         'taxonomies' => array( Category::$CPT ),
         'has_archive' => true
      ) );

      // Хуки
      add_filter( 'manage_edit-' . self::$CPT . '_columns', array( __CLASS__, 'get_post_columns' ), 25 ); // Добавление колонок в админке
      add_action( 'manage_posts_custom_column', array( __CLASS__, 'show_post_columns' ), 25, 2 ); // Значения колонок в админке
   }

   /**
    * Список колонок в админке
    * @static
    * @param array $columns      Список существующих колонок
    */
   public static function get_post_columns( $columns) {

      $new_columns = array();
      $new_columns[ 'coordinator' ] = __('Координатор проекта', CPM);
      $new_columns[ 'is_active' ] = __('Активный проект', CPM);
      $new_columns[ 'is_archive' ] = __('Архивный проект', CPM);

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
         case 'coordinator':
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
            'coordinator' => $item->coordinator,
            'is_archive'  => $item->is_archive,
            'is_active'   => $item->is_active 
        ) );
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
      return "SELECT
            ID,
            MAX(post_author) AS post_author,
            MAX(post_date) AS post_date,
            MAX(post_content) AS post_content,
            MAX(post_title) AS post_title,
            MAX(post_name) AS post_name,
            MAX(post_parent) AS post_parent,
            MAX(menu_order) AS menu_order,
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
         -- ORDER --
         -- LIMIT --
      ";
   }

   /**
    * Метод подготавливает параметры SQL запроса по массиву параметров
    * @static
    * @param mixed    $args     Массив параметров запроса (GET-параметры и др.)
    * @return array             Метод возвращает массив с подготовленными параметрами
    *                           array( 'WHERE' => array(...), 'LIMIT' => '...' ) 
    */
    protected static function get_sql_args( $args = array() )
    {
       global $wpdb;
 
       $sql_args = parent::get_sql_args( $args );   
 
       // Координатор проекта
       if ( isset( $args[ 'coordinator' ] ) ) {
          $sql_args['WHERE'][] = $wpdb->prepare( '_cpm_coordinator = %s',  $args[ 'coordinator' ] );
       }
 
       // Архивный проект
       if ( isset( $args[ 'is_archive' ] ) ) {
          $sql_args['WHERE'][] = $wpdb->prepare( '_project_archive = %s',  $args[ 'is_archive' ] );
       }
 
       // Активный проект
       if ( isset( $args[ 'is_active' ] ) ) {
          $sql_args['WHERE'][] = $wpdb->prepare( '_project_active = %s',  $args[ 'is_active' ] );
       }
 
       return $sql_args;
    }


   /**
    * По умолчанию возвращаем только активные проекты
    * @static
    * @param array    $args     Параметры запроса
    */
   public static function read_list( $args=array() ) {
      return parent::read_list( array_merge( array( 
               'is_archive' => 'no', 
               'is_active'  => 'yes',
               'orderby'    => 'menu_order DESC, post_title ASC'
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
   private function get_old_style_members( $project_id ) 
   {
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