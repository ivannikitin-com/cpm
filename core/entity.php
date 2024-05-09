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
     * @var sting
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
        // Инициализация полей объекта
        foreach ($args as $field => $value) {
            switch ($field) {
                case 'id':
                case 'ID':
                    $this->ID = $value;
                    continue;

                case 'title':
                case 'post_title':
                    $this->title = $value;
                    continue;

                case 'content':
                case 'post_content':
                    $this->content = $value;
                    continue;

                case 'slug':
                case 'post_name':
                    $this->slug = $value;
                    continue;

                case 'author':
                case 'post_author':
                    $this->author = $value;
                    continue;

                case 'parent':
                case 'post_parent':
                    $this->parent = $value;
                    continue;
                    
                case 'created':
                case 'post_date':
                    $this->created = $value;
                    continue;
                    
                case 'order':
                case 'menu_order':
                    $this->order = $value;
                    continue;

                case 'team_serialized':
                    if ( !empty( $value ) ) {
                        $this->team = unserialize( $value );
                    }
                    else {
                        $this->team = new Team();
                    }
                    continue;

                default:
                    // Через магические свойства пишем мета-поля
                    $this->$field = $value;
            }
        }
    }


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
        )
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
        return self::updateEntity( $this );
    }

    /**
     * Метод удаляет объект в БД
     */
    public function delete()
    {
        return self::deleteEntity( $this );
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
        MAX(CASE WHEN pm.meta_key = 'team' THEN pm.meta_value ELSE NULL END) AS _team
    FROM
        {$wpdb->posts} p
            INNER JOIN {$wpdb->postsmeta} pm
                ON p.ID = pm.post_id
    WHERE
        post_type = '{$cpt}'
        -- EXTRA_WHERE --
    GROUP BY
        ID
SQL;
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
            $query = $wpdb->query(
                $wpdb->prepare( 
                    str_replace( '-- EXTRA_WHERE --', 'AND ID = %d', static::get_sql() ),
                    $id 
                )
            );
        }
        else {
            // Если передан слаг, то запрашиваем по слагу
            $query = $wpdb->query(
                $wpdb->prepare( 
                    str_replace( '-- EXTRA_WHERE --', 'AND post_name = %s', static::get_sql() ),
                    $id 
                )
            );
        }
    
        $post = $wpdb->get_row( $sql, ARRAY_A );
        if ( ! $post ) return null;

        // Создание сущности
        return new static( $post );;
    }

    /**
     * Метод считывает массив объектов из БД
     * @static
     * @param array    $args     Параметры запроса
     */
    public static function readList( $args ) {
        global $wpdb;
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
        $posts = $wpdb->query( 
            $wpdb->prepare( str_replace( '-- EXTRA_WHERE --', $where, static::get_sql() ), $params ), 
            ARRAY_A 
        );
        
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
        $cache = wp_cache_get( $entity_name, $class );
        if ( $cache ) return $cache;
    
        // Запрашиваем сущность из ее класса
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
    public static function getList( $args = array() ) {
        
        // Поиск сущностей в кэше
        $class = static::get_class_name();
        $entities_array = $class . '_list';
        $list_id = md5( serialize( $args ) );

        // Проверка наличия списка в кэше
        $entities_array = wp_cache_get( $entity_list, $class );
        if ( $entities_array && isset( $entities_array[ $list_id ] ) ) {
            return $entities[ $list_id ];
        }
        
        // Списка в кэше нет. Запрашиваем его из БД
        $entities = $class::getList( $args );
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
        $entities_array = $class . '_list';
        if ( $id ) wp_cache_delete( $entity_name, $class ); // Сущности без ID не обрабатываем
        wp_cache_delete( $entities_array, $class );
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
        }
        else {
            wp_cache_flush();
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
    static public function update( $entity )
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
    static public function delete( $entity )
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
}