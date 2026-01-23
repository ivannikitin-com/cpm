<?php
/**
 * Класс сущности
 * Реализует методы для работы с сущностями
 */

namespace CPM\Core;

class Entity
{

    /* ----------------- Константы ----------------- */
    /**
     * @static const string
     * CPT сущности
     */
    const CPT = 'cpm_entity';

    /* ----------------- Свойства объекта WP ----------------- 
     *
     * Эти свойства являются свойствами объекта WP_Post, 
     * то есть они соответствуют полям таблицы wp_posts
     */

    /**
     * @var int
     * Идентификатор объекта
     */
    public $id;

    /**
     * @var string
     * Название объекта
     */
    public $title;

    /**
     * @var string
     * Контент объекта
     */
    public $content;

    /**
     * @var string
     * Автор объекта
     */
    public $author_id;

    /**
     * @var string
     * Дата создания объекта
     */
    public $created_at;

    /**
     * Родительская сущность
     * @var int
     */
    public $parent = 0;

    /**
     * Слаг сущности
     * @var string
     */
    public $slug = '';


    /**
     * Возвращает массив параметров со значениями по умолчанию
     * Для инициализации сущности и всех последующих объектов данных
     * используется массив с параметрами. Названия параметров максимально
     * совпадают с названиями свойств объектов WP и полей в БД.
     * Но следует всегда придерживаться названий в возвращаемом здесь массиве. 
     */
    public static function get_default_params(){
        return [
            'id' => null,
            'title' => null,
            'content' => null,
            'author_id' => null,
            'created_at' => null,
            'parent' => null,
            'slug' => null
        ];
    }

    /**
     * Конструктор сущности
     * Инициализирует свойства объекта
     * @param mixed params массив параметров
     * @return void
     */
    public function __construct( $params = [] ) {
        $params = array_merge( static::get_default_params(), $params );
        $this->id = $params['id'];
        $this->title = $params['title'];
        $this->content = $params['content'];
        $this->author_id = $params['author_id'];
        $this->created_at = $params['created_at'];
        $this->parent = $params['parent'];
        $this->slug = $params['slug'];
    }


    /* ------------------------- Мета свойства -------------------------
     *
     * Остальные свойства являются мета-свойствами 
     * и хранятся в массиве $meta.
     * Доступ к ним осуществляется через магические методы 
     * __get и __set.
     */

    /**
     * Статический метод возвращает стандартный набор мета-полей
     * для сущности. Используется для начальной инициализации объекта
     * и для наглядности, какие мета-свойства у объекта есть.     
     * @static
     * @return array
     */
    protected static function get_meta_fields_default()
    {
        return array(
            '_team' => []
        );
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
     * В массиве ключ - имя свойства, значение - имя мета-поля
     * Фактически он нужен для преобразования ряда свойств в старые мета-поля
     * например, для обратной совместимости с предыдущими версиями
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

    /* ------------------------- Методы ------------------------- */
    /**
     * Конструктор
     * @param int    $id          идентификатор объекта (необязательный, если не передан, то создается новый объект, иначе загружается объект из БД)
     * @param string $title       название объекта 
     * @param string $content     контент объекта 
     * @param int    $author_id   идентификатор автора объекта 
     * @param string $created_at  дата создания объекта
     * @param int    $parent      идентификатор родительской сущности
     * @param string $slug        слаг сущности
     * @param array  $meta        мета-свойства
     */
    public function __construct_1( $id = null, $title = null, $content = null, $author_id = null, $created_at = null, $parent = null, $slug = null, $meta = [] ) {
        // Сохраняем параметры в свойства
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->author_id = $author_id;
        $this->created_at = $created_at;
        $this->parent = $parent;
        $this->slug = $slug;

        // Если не передан идентификатор, то создаем новый объект
        if ( empty( $id )  ) {
            // Заполняем недостающие параметры
            $this->title = $title ?? '';
            $this->content = $content ?? '';
            $this->author_id = $author_id ?? get_current_user_id();
            $this->created_at = $created_at ?? time();
            $this->parent = $parent ?? 0;
            $this->slug = $slug ?? '';

            // Создаем новый объект
            $this->id = wp_insert_post( [
                'post_type' => self::CPT,
                'post_title' => $title,
                'post_content' => $content,
                'post_author' => $author_id,
                'post_date' => $created_at,
                'post_parent' => $parent,
                'post_name' => $slug,
            ] );

            // Заполняем мета-свойства
            $this->meta = array_merge( static::get_meta_fields_default(), $meta );         
            return $this;
        }   

        // Если передан идентификатор, но пустые параметры, то загружаем объект из БД
        if ( empty( $title ) && empty( $content ) && empty( $author_id ) ) {
            // Загружаем объект из БД
            $post = get_post( $id );
            if ( $post ) {
                // Проверяем, является ли объект сущностью
                if ( $post->post_type !== self::CPT ) {
                    throw new \Exception( 'Объект не является сущностью' );
                }
                // Заполняем свойства объекта
                $this->id = $post->ID;
                $this->title = $post->post_title;
                $this->content = $post->post_content;
                $this->author_id = $post->post_author;
                $this->parent = $post->post_parent;
                $this->slug = $post->post_name;

                // Прочитаем мета-свойства из БД
                $this->meta = array_merge( static::get_meta_fields_default(), wp_get_post_meta( $this->id ) );         
            }
            else {
                throw new \Exception( 'Объект не найден' );
            }
            return $this;
        }
    }

    /**
     * Обновление объекта в БД
     */
    public function update() {
        // Проверяем, существует ли объект
        if ( empty( $this->id ) ) {
            throw new \Exception( 'Объект с пустым ID не может быть обновлен' );
        }

        // Обновляем объект в БД
        wp_update_post( [
            'ID' => $this->id,
            'post_title' => $this->title,
            'post_content' => $this->content,
            'post_author' => $this->author_id,
            'post_parent' => $this->parent,
            'post_name' => $this->slug,
        ] );

        // Обновляем мета-свойства
        foreach ( $this->meta as $key => $value ) {
            update_post_meta( $this->id, $key, $value );
        }

        return $this;
    }

    /**
     * Удаление объекта из БД
     */
    public function delete() {
        // Проверяем, существует ли объект
        if ( empty( $this->id ) ) {
            throw new \Exception( 'Объект с пустым ID не может быть удален' );
        }

        // Удаляем объект из БД
        wp_delete_post( $this->id );
        return $this;   
    } 

    /* ------------------------- Статические методы -------------------------
     *                 реализуют операции со списками объектов
     */


    /**
     * Создает объект сущности из массива параметров
     * Этот метод используется для создания объекта из результатов запроса
     * @static
     * @param array $params массив параметров запроса
     * @return Entity
     */
    public static function create( $params = [] ) {
        // Если не переданы параметры, то возвращаем null
        if ( empty( $params ) ) {
            return null;
        }

        // Создаем объект
        try {
            $object = new static( 
                $params['ID'],                  // идентификатор объекта
                $params['post_title'],          // название объекта
                $params['post_content'],        // контент объекта
                $params['post_author'],         // идентификатор автора объекта
                $params['post_date'],           // дата создания объекта
                $params['post_parent'],         // идентификатор родительской сущности
                $params['post_name'],           // слаг сущности
                [ '_team' => $params['_team'] ] // мета-свойства
             );
        }
        catch ( \Exception $e ) {
            // Если объект не может быть создан, то пропускаем его
            $object = null;
        }        
        return $object;
    }

    /**
     * @static
     * @param array $params массив параметров запроса
     * @return mixed
     * Возвращает массив объектов прямым запросом к БД
     */
    public static function get( $params = [] ) {
        $sql = self::prepare_where_clause( $params, 'HAVING' ) . 
               self::prepare_order_by_clause( $params ) . 
               self::prepare_limit_clause( $params );
        
        // Выполняем запрос
        $results = $wpdb->get_results( $sql, ARRAY_A );
        
        // Создаем объекты из результатов запроса
        $objects = [];
        foreach ( $results as $result ) {
            // Если объект создан, то добавляем его в массив
            $object = self::create( $result );
            if ( $object ) {
                $objects[] = $object;
            }
        }
        return $objects;
    }

    /**
     * @return string
     * Возвращает SQL запрос для получения объектов
     */
    protected static function get_sql() {
        global $wpdb;
        $cpt = static::CPT;
        return "SELECT
                    ID,
                    MAX(post_author) AS post_author,
                    MAX(post_date) AS post_date,
                    MAX(post_content) AS post_content,
                    MAX(post_title) AS post_title,
                    MAX(post_name) AS post_name,
                    MAX(post_parent) AS post_parent,
                    MAX(menu_order) AS menu_order,
                    MAX(CASE WHEN pm.meta_key = 'team' THEN pm.meta_value ELSE NULL END) AS _team
                FROM
                    {$wpdb->posts} p
                        INNER JOIN {$wpdb->postmeta} pm
                            ON p.ID = pm.post_id
                WHERE
                    post_type = '{$cpt}'
                GROUP BY
                    ID
        ";                      
    }

    /**
     * Подготавливает параметры WHERE запроса и вставляет их в SQL запрос
     * @param array $params массив параметров запроса
     * @param string $clause WHERE или HAVING
     * @return string
     * Возвращает SQL запрос с подставленными параметрами
     */
    protected static function prepare_where_clause( $params, $clause = 'WHERE' ) {
        global $wpdb;
        $sql = self::get_sql();
        if ( !empty( $params ) ) {
            $where = [];
            foreach ( $params as $key => $value ) {
                switch ( $key ) {
                    case 'id':
                        $where[] = $wpdb->prepare( "ID = %d", $value );
                        break;
                    case 'title':
                        $where[] = $wpdb->prepare( "post_title LIKE %s", '%' . $value . '%' );
                        break;
                    case 'content':
                        $where[] = $wpdb->prepare( "post_content LIKE %s", '%' . $value . '%' );
                        break;
                    case 'author_id':
                        $where[] = $wpdb->prepare( "post_author = %d", $value );
                        break;
                    case 'created_at':
                        $where[] = $wpdb->prepare( "post_date = %s", $value );
                        break;
                }
            }
            $sql .= " {$clause} 1=1 " . implode( " AND ", $where );
        }
        return $sql;
    }

    /**
     * Подготавливает параметры ORDER BY запроса и вставляет их в SQL запрос
     * @param array $params массив параметров запроса
     * @return string
     * Возвращает SQL запрос с подставленными параметрами
     */
    protected static function prepare_order_by_clause( $params ) {
        $sql = self::get_sql(); 
        foreach ( $params as $key => $value ) {
            $sql = str_replace( '{{' . $key . '}}', $value, $sql );
        }
        return $sql;
    }

    /**
     * Подготавливает параметры LIMIT запроса и вставляет их в SQL запрос
     * @param array $params массив параметров запроса
     * @return string
     * Возвращает SQL запрос с подставленными параметрами
     */
    protected static function prepare_limit_clause( $params ) {
        $sql = self::get_sql(); 
        foreach ( $params as $key => $value ) {
            $sql = str_replace( '{{' . $key . '}}', $value, $sql );
        }
        return $sql;
    }    
}