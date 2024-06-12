<?php
/**
 * Базовый класс сущностей CPM
 * Обеспечивает низкоуровенную работу с сущностями CPM (проекты, задачи и т.п.)
 * 
 * Свойства класса непосредственно реализованные отражают свойства \WP_Post
 * Все остальные свойства хранятся в массиве $meta и сохраняются как мета-данные WordPress.
 * Любые дополнительные свойства реализованы магическими методами __get() и __set()
 * 
 * Метод get_meta_fields возвращает массив свойства имени свойства и мета-поля,
 * это необходимо для обратной совместимости со старыми версиями CPM.
 * Исключения -- свойства объектного типа, например, Team. Они хранятся отдельно и записываются в 
 * мета-поля в момент обновления данных в БД.
 * 
 * Чтение данных из БД осуществляется прямыми запросами SQL для ускорения и снижения
 * количества обращений к БД, а запись в БД выполняется через API WordPress для правильной реализации
 * всех механизмов (хуков и т.п.)
 * 
 * @author Ivan Nikitin
 * @package CPM
 * @version 3.0.0
 */

namespace CPM\Core;

class Entity
{
    /**
     * Тип сущности
     * Должно быть переопределено в наследнике
     */
    static public $CPT = 'cpm_entity';

   /* ------------------ Свойства и методы сущности ------------------ */

    /**
     * ID сущности
     * @var int
     */
    public $ID = 0;

    /**
     * Название сущности
     * @var string
     */
    public $title = '';

    /**
     * Контент сущности
     * @var string
     */
    public $content = '';

    /**
     * Слаг сущности
     * @var string
     */
    public $slug = '';

    /**
     * Автор сущности
     * @var int
     */
    public $author = 0;

    /**
     * Родительская сущность
     * @var int
     */
    public $parent = 0;

    /**
     * Дата создания сущности в формате MySQL YYYY-MM-DD HH:MM:SS
     * @var string
     */
    public $created = 0; 

    /**
     * Порядковый номер в списке
     * @var int
     */
    public $order = 0; 

    /**
     * Участники сущности
     * @var Team
     */
    public $team = null;


    /**
     * Конструктор объекта сущности
     * Создает новый объект сущности CPM
     * @param mixed    $args     Массив полей объекта, принимает массив полей \WP_Post
     * @see \WP_Post              
     */
    public function __construct( $args = array() )
    {
        // Инициализация команды
        $this->team = new Team();

        // Инициализация полей объекта
        foreach ($args as $field => $value) {
            switch ($field) {
                case 'id':
                case 'ID':
                    $this->ID = $value;
                    continue 2;

                case 'title':
                case 'post_title':
                    $this->title = $value;
                    continue 2;

                case 'content':
                case 'post_content':
                    $this->content = $value;
                    continue 2;

                case 'slug':
                case 'post_name':
                    $this->slug = $value;
                    continue 2;

                case 'author':
                case 'post_author':
                    $this->author = $value;
                    continue 2;

                case 'parent':
                case 'post_parent':
                    $this->parent = $value;
                    continue 2;
                    
                case 'created':
                case 'post_date':
                    $this->created = $value;
                    continue 2;
                    
                case 'order':
                case 'menu_order':
                    $this->order = $value;
                    continue 2;

                case 'team_serialized':
                    if ( !empty( $value ) ) {
                        $this->team = unserialize( $value );
                    }
                    continue 2;

                default:
                    // Через магические свойства пишем мета-поля
                    $this->$field = $value;
            }
        }
    }

    /* ----------------------- Мета-поля сущности -------------------- */
    /**
     * Массив мета-полей
     * Свойство открыто как public, потому что е нему обращаются статические методы
     * @var array
     */
    public $meta = array();

    /**
     * Переменная которая хранит массив имен мета-полей
     * Сделано для ускорения, чтобы каждый раз массив не создавать
     * @static array
     */
    protected static $meta_fields = null;

    /**
     * Метод возвращает массив соответствия имен мета-полей
     * @return array
     */
    protected static function get_meta_fields()
    {
        return array(
            'team_serialized' => '_team'
        );
    }

    /**
     * Магический метод, обрабатывающий чтение дополнительных свойств объекта
     * @param string    $name     Имя свойства
     * @return mixed              Значение свойства
     */
    public function __get( $name )
    {
        // Проверим массив соответствия имен мета-полей
        if ( empty( static::$meta_fields ) ) static::$meta_fields = static::get_meta_fields();
        // Если есть поле в массиве соответствия
        if ( isset( static::$meta_fields[ $name ] ) ) {
            // вернем его значение
            return isset( $this->meta[ static::$meta_fields[ $name ] ] ) ? 
                $this->meta[ static::$meta_fields[ $name ] ] : 
                null;
        }
        else {
            // Вернем просто значение поля
            return isset( $this->meta[ $name ] ) ? $this->meta[ $name ] : null;
        }
    }

    /**
     * Магический метод, обрабатывающий запись дополнительных свойств объекта
     * @param string    $name     Имя свойства
     * @param mixed     $value    Значение свойства
     */
    public function __set( $name, $value )
    {
        // Проверим массив соответствия имен мета-полей
        if ( empty( static::$meta_fields ) ) static::$meta_fields = static::get_meta_fields();
        // Если есть поле в массиве соответствия
        if ( isset( static::$meta_fields[ $name ] ) ) {
            // запишем его значение с названием мета-поля
            $this->meta[ static::$meta_fields[ $name ] ] = $value;
        }
        else {
            // Просто запишем значение поля с его именем
            $this->meta[ $name ] = $value;
        }
    }

    /* ----------------------- Сервисные методы ----------------------- */
    /**
     * Фильтрация слага
     * Необходимо чтобы не было конфликтов в URL, например
     * /project/my_proj/messages/ выводит сообщения проекта, 
     * а не список задач со слагом messages 
     */
    protected function filter_slug( $slug ) {
        // Зарезервированные ключевые слова недопустимые в слагах
        $internal_slug_keywords = apply_filters( 'cpm_internal_slug_keywords', array(
            'dashboard',
            'projects',
            'messages',
            'milestones',
            'tasks',
            'files',
            'settings'
        ));

        // Конвертируем в нижний регистр
        $new_value = str_tolower( $slug );

        // Проверка и коррекция если слаг -- это зарезервированное ключевое слово
        if ( in_array( $new_value, $internal_slug_keywords ) ) {
            $new_value = $new_value . '_' . crc32( $this->title . rand() );
        }

        // Убираем пробелы и др. символы
        $new_value = preg_replace( '/[^a-zа-я0-9\-_]/', '_', $new_value );

        // Возвращаем новый слаг
        return apply_filters( 'cpm_filter_slug', $new_value, $slug, $this );
    }

    /**
     * Метод обновляет объект в БД
     * Может быть перекрыт в потомках
     */
    public function update()
    {
        // Фильтрация слага
        $this->slug = $this->filter_slug( $this->slug );

        // Сохранение объектных свойств в мета-полях
        $this->meta['_team_serialized'] = serialize( $this->team );
        return self::updateObject( $this );
    }

    /**
     * Метод удаляет объект в БД
     */
    public function delete()
    {
        return self::deleteObject( $this );
    }

    /* --------------------- Работа с БД ------------------ */
 
    /**
     * Метод возвращает SQL запрос чтения сущности без WHERE
     * @static
     * @return string
     */
    protected static function get_sql()
    {
        global $wpdb;
        $cpt = static::CPT;
        return <<<END_SQL
            SELECT
                    ID,
                    post_author,
                    post_date,
                    post_content,
                    post_title,
                    post_name,
                    post_parent,
                    menu_order,
                    MAX(CASE WHEN pm.meta_key = 'team' THEN pm.meta_value ELSE NULL END) AS _team
                FROM
                    {$wpdb->posts} p
                        INNER JOIN {$wpdb->postmeta} pm
                            ON p.ID = pm.post_id
                WHERE
                    post_type = '{$cpt}'
                    -- EXTRA_WHERE --
                GROUP BY
                    ID
        END_SQL;
    }


    /**
     * Метод считывает объект из БД по ID или по слагу
     * Все чтения из БД выполняются прямыми запросами для ускорения
     * @static
     * @param int|string    $id     идентификатор или слаг сущности
     * @return object
     */
    static protected function read( $id )
    {
        global $wpdb;

        // Если передан ID как int, запрашиваем по ID
        if ( is_numeric( $id ) ) {
            $query = $wpdb->prepare( 
                    str_replace( '-- EXTRA_WHERE --', 'AND ID = %d', static::get_sql() ),
                    $id 
                );
        }
        else {
            // Если передан слаг, то запрашиваем по слагу
            $query = $wpdb->prepare( 
                    str_replace( '-- EXTRA_WHERE --', 'AND post_name = %s', static::get_sql() ),
                    $id 
            );
        }
    
        $post = $wpdb->get_row( $query, ARRAY_A );
        if ( ! $post ) return null;

        // Создание сущности
        return new static( $post );;
    }

    /**
     * Метод считывает массив объектов из БД
     * @static
     * @param array    $args     Параметры запроса
     */
    public static function read_list( $args=array() ) {
        global $wpdb;

        \CPM\Plugin::get_instance()->log( self::class . '::read_list() args: ' . var_export( $args, true ), 'debug' );
        $where = '';
        $params = array();

        foreach ($args as $field => $value) {
            $params[] = $value;
            if ( is_int( $value ) ) {
                $where .= " AND {$field} = %d";
            }
            elseif ( is_numeric( $value ) ) {
                $where .= " AND {$field} = %f";
            }
            else {
                $where .= " AND {$field} = %s";
            }
        }

        // Запрос
        $sql = $wpdb->prepare( 
            str_replace( '-- EXTRA_WHERE --', $where, static::get_sql() ),
            $params
        );
        \CPM\Plugin::get_instance()->log( 'read_list SQL: ' . PHP_EOL. $sql, 'SQL' );

        // Выполнение запроса
        $posts = $wpdb->get_results( $sql, ARRAY_A );
        
        // Создание массива сущностей
        $entities = array();
        foreach ( $posts as $post ) {
            $entity = new static( $post );
            $entities[] = $entity;
        }
        return $entities;
    }

    /**
     * Метод сохраняет объект в БД
     * Важно! Все мета-данные должны быть подготовлены в объекте перед вызовом!
     * @static
     * @param Entity    $entity     Объект сущности
     */
    public static function write( $entity ) {
        // Данные для обновления
        $post_data = apply_filters( static::get_class_name() . '_before_write_post_data' , 
            array(
                'ID'            => $entity->ID,
                'post_title'    => $entity->title,
                'post_content'  => $entity->content,
                'post_name'     => $entity->slug,
                'post_status'   => 'publish',
                'post_type'     => static::CPT,
                'post_author'   => $entity->author,
                'ping_status'   => get_option('default_ping_status'),
                'post_parent'   => $entity->parent,
                'menu_order'    => $entity->order,
                'to_ping'       => '',
                'pinged'        => '',
                'post_password' => '',
                'post_excerpt'  => '',
                'meta_input'    => $entity->meta
            )
        );

        // Сохранение записи в БД
        return wp_insert_post(  wp_slash( $post_data ) );
    }

    /* ------------------ Работа с кэшем ------------------ */

    /**
     * Метод возвращает название вызывающего класса в нижнем регистре
     * и с разделителем "_". Используется для кэширования и хуков
     * @static
     */
    protected static function get_class_name() {
        return strtolower( str_replace( '\\', '_', static::class ) );
    }

    /**
     * Метод возвращает объект сущности CPM из кэша
     * @static
     * @param int|string    $id     идентификатор сущности
     * @return Entity
     */
    public static function get( $id )
    {
        // Поиск сущности в кэше
        $class = static::get_class_name();
        $entity_name = $class . '_' . $id;

        $debug_log_string = "CPM: Entity cache:"  . $entity_name;
        $cache = wp_cache_get( $entity_name, $class );
        if ( $cache ) {
            $debug_log_string .= ' hit!';
            \CPM\Plugin::get_instance()->log( $debug_log_string, 'debug' );
            return $cache;
        }

        // Запрашиваем сущность из ее класса
        $debug_log_string .= ' miss!';
        $entity = static::read( $id );
        // Если удалось прочитать, сохраняем в кэш
        if ( $entity ) {
            wp_cache_add( $entity_name, $entity, $class );
        }
        // Возвращаем сущность
        return $entity;
    }

    /**
     * Метод возвращает массив объектов сущностей CPM
     * предназначен для централизованного кэширования списков
     * @static
     * @param mixed         $args   параметры запроса
     */
    public static function get_list( $args = array() ) {
        
        // Поиск сущностей в кэше
        $class = static::get_class_name();
        $entities_array = $class . '_list';
        $list_id = md5( serialize( $args ) );
        $debug_log_string = "CPM: Entity list cache:"  . $entities_array;

        // Проверка наличия списка в кэше
        $entities_array = wp_cache_get( $entities_array, $class );
        if ( $entities_array && isset( $entities_array[ $list_id ] ) ) {
            $debug_log_string .= ' hit!';
            \CPM\Plugin::get_instance()->log( $debug_log_string, 'debug' );
            return $entities[ $list_id ];
        }
        
        // Списка в кэше нет. Запрашиваем его из БД
        $debug_log_string .= ' miss! Calling ' . static::class . '::read_list()...';
        \CPM\Plugin::get_instance()->log( $debug_log_string, 'debug' );
        $entities = static::read_list( $args );
        // Если удалось прочитать, сохраняем в кэш
        if ( count( $entities ) > 0 ) {
            wp_cache_add( $entities_array, array( $list_id => $entities ), $class );
        }
        return $entities;
    }

    /**
     * Метод удаляет объект в кэше
     * @static
     * @param int|string    $id     идентификатор сущности
     */
    static public function deleteCache( $id )
    { 
        $class = static::get_class_name();
        $entity_name = $class . '_' . $id;
        if ( $id ) {
            // Сущности без ID не обрабатываем
            wp_cache_delete( $entity_name, $class );
            \CPM\Plugin::get_instance()->log( 'CPM: Entity cache deleted: ' . $entity_name, 'debug' );
        }

        $entities_array = $class . '_list';
        wp_cache_delete( $entities_array, $class );
        \CPM\Plugin::get_instance()->log( 'CPM: Entity list cache deleted: ' . $entities_array, 'debug' );
    }

    /**
     * Метод удаляет все объекты в кэше определенного класса
     * @static
     */
    static public function flushCache( ) 
    {
        $class = static::get_class_name();
        if ( wp_cache_supports('flush_group') ) {
            wp_cache_flush_group( $class );
            \CPM\Plugin::get_instance()->log( 'CPM: Entity cache group flushed: ' . $class, 'debug' );
        }
        else {
            wp_cache_flush();
            \CPM\Plugin::get_instance()->log( 'CPM: Entity cache flushed', 'debug' );
        }
    }

    /* -------------- Низкоуровневая работа с сущностями -------------- */

    /**
     * Метод добавляет или обновляет сущность БД 
     * и вызывает хуки создания и обновления
     * @static
     * @param Entity    $entity     Объект сущности
     * @return bool
     */
    static public function updateObject( $entity )
    {
        // Класс сущности
        $class = static::get_class_name();
        // Имя хука для создания или обновления
        $hook = ( 0 == $entity->ID ) ? $class . '_create' : $class . '_update';   
        // Выполняем операцию
        $result = static::write( $entity );       
        // Если успешно ...
        if ( $result ) {
            // Удаляем из кэша объект и список типа объектов
            self::deleteCache( $entity->ID );
            // вызываем хук
            do_action( $hook, $entity );            
        } 
        // Возвращаем результат 
        return $result;
    }

    /**
     * Метод удаляет сущность из БД
     * @static
     * @param Entity    $entity     Объект сущности
     * @return bool
     */
    static public function deleteObject( $entity )
    {
        // Имя хука
        $hook = static::get_class_name() . '_delete';
        // Выполняем операцию
        $result = wp_delete_post( $entity->ID, true );
        // Если успешно ...
        if ( $result ) {
            // Удаляем из кэша объект и список типа объектов
            self::deleteCache( $entity->ID );
            // вызываем хук
            do_action( $hook, $entity );            
        } 
        // Возвращаем результат 
        return $result;
    }

    /* ----------------------- Права доступа ------------------------ */
    /**
     * Проверка прав доступа двухэтапная:
     *   1. Проверка на доступ к операциям API осуществляется на 
     *      основе ролей пользователей (реализовано в REST Controller)
     * 
     *   2. Проверка на доступ к объектам сущностей осуществляется
     *      на основе ролей CPM
     */

    /* ------------------- Права доступа к API  -------------------- */
    /**
     * Статический метод возвращает массив допустимых CRUD операций в API
     * для ролей WordPress.
     * Проверка на доступ к операциям осуществляется в классе Controller
     * @return array      
     */
    public static function get_wp_role_api_permissions()
    {
        return apply_filters( 'cpm_api_role_permissions', array(
            'administrator' => array( 'create', 'read', 'update', 'delete' ),  // Администратор
            'employee'      => array( 'create', 'read', 'update', 'delete' ),  // Сотрудник
            'lumper'        => array( 'create', 'read', 'update', 'delete' ),  // Подрядчик
            'agent'         => array( 'create', 'read', 'update', 'delete' ),   // Представитель
            'customer'      => array( 'create', 'read', 'update', 'delete' )   // Заказчик
        ), static::class );
    }


   /* -------------------- REST API ------------------- */

   /**
    * Свойства REST API
    */
    public static $rest_api = true;         // Этот объект доступен в REST API
    public static $rest_base = 'object';    // База сущности в URI


    /**
     * Метод возвращает схему сущности для REST API
     * @static
     * @return array
     */ 
    static public function get_rest_schema()
    {
		return array(
			// показывает какую версию схемы мы используем - это draft 4
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			// определяет ресурс который описывает схема
			'title'      => static::$rest_base,
			'type'       => 'object',
			// в JSON схеме нужно указывать свойства объекта в атрибуте 'properties'.
			'properties' => array(
				'id' => array(
					'description' => __('Уникальный идентификатор объекта', CPM),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'title' => array(
					'description' => __('Название объекта', CPM),
					'type'        => 'string',
				),
				'content' => array(
					'description' => __('Содержимое объекта', CPM),
					'type'        => 'string',
                ),
				'slug' => array(
					'description' => __('Содержимое объекта', CPM),
					'type'        => 'string',
                ),
				'author' => array(
					'description' => __('ID автора объекта', CPM),
					'type'        => 'int',
                ),
				'parent' => array(
					'description' => __('ID автора объекта', CPM),
					'type'        => 'int',
                ),
				'created' => array(
					'description' => __('Дата создания объекта YYYY-MM-DD HH:MM:SS', CPM),
					'type'        => 'string',
                ),                
				'order' => array(
					'description' => __('Порядковый номер в списке', CPM),
					'type'        => 'int',
                ),
                'team' => array(
                    'description' => __('Участники', CPM),
                    'type'        => 'array',
                    'items'       => array(
                        'type' => 'object',
                        'properties' => array(
                            'id' => array(
                                'description' => __('ID участника', CPM),
                                'type'        => 'int',
                            ),
                            'role' => array(
                                'description' => __('Роль участника', CPM),
                                'type'        => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        );
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
        return array(
            'id'        => $item->ID,
            'title'     => $item->title,
            'content'   => $item->content,
            'slug'      => $item->slug,
            'author'    => $item->author,
            'parent'    => $item->parent,
            'created'   => $item->created,
            'order'     => $item->order,
            'team'      => $item->team         
        );
    }
}