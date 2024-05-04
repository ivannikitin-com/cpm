<?php
/**
 * Базовый класс сущностей CPM
 * Обеспечивает низкоуровенную работу с сущностями CPM
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

    /* ------------------ Работа с кэшем ------------------ */

    /**
     * Метод возвращает объект сущности CPM из кэша
     * @param int|string    $id     идентификатор сущности
     * @param string        $class  имя класса сущности
     * @return object
     */
    public static function getEntity( $id, $class )
    {
        // Поиск сущности в кэше
        $entity_name = $class . '_' . $id;
        $cache = wp_cache_get( $entity_name, $class );
        if ( $cache ) return $cache;
    
        // Запрашиваем сущность из ее класса
        $entity = new $class( $id );
        wp_cache_add( $entity_name, $entity, $class );
        return $entity;
    }

    /**
     * Метод возвращает массив объектов сущностей CPM
     * предназначен для централизованного кэширования списков
     * @param string        $class  имя класса сущности
     * @param mixed         $args   параметры запроса
     */
    public static function getEntities( $class, $args = array() ) {
        
        // Поиск сущностей в кэше
        $entities_array = $class . '_list';
        $list_id = md5( serialize( $args ) );

        // Проверка наличия списка в кэше
        $entities_array = wp_cache_get( $entity_list, $class );
        if ( $entities_array && isset( $entities_array[ $list_id ] ) ) {
            return $entities[ $list_id ];
        }
        
        // Списка в кэше нет. Запрашиваем его из БД
        $entities = $class::getList( $args );
        wp_cache_add( $entities_array, array( $list_id => $entities ), $class );
        return $entities;
    }


    /**
     * Метод удаляет объект в кэше
     * @param int|string    $id     идентификатор сущности
     * @param string        $class  имя класса сущности
     */
    static public function deleteCache( $id, $class )
    {
        $entity_name = $class . '_' . $id;
        $entities_array = $class . '_list';
        wp_cache_delete( $entity_name, $class );
        wp_cache_delete( $entities_array, $class );
    }

    /**
     * Метод удаляет все объекты в кэше определенного класса
     * @param string        $class  имя класса сущности 
     */
    static public function flushCache( $class ) 
    {
        if ( wp_cache_supports('flush_group') ) {
            wp_cache_flush_group( $class );
        }
        else {
            wp_cache_flush();
        }
    }

    /* ------------------ Работа с сущностями ------------------ */

    /**
     * Метод создает новый объект в БД
     * Возвращает ID созданной сущности
     * @param string  $title      Название
     * @param string  $content    Описание
     * @param string  $slug       Слаг
     * @param mixed   $data       Дополнительные данные
     * @return int
    */
    public static function create( $title, $content, $slug, $data )
    {
        // Поля сущности по умолчанию
        $defaults = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_name'     => $slug,
            'post_type'     => self::$CPT,           
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
            'ping_status'   => get_option('default_ping_status'),
            'post_parent'   => 0,
            'menu_order'    => 0,
            'to_ping'       => '',
            'pinged'        => '',
            'post_password' => '',
            'post_excerpt'  => '',
            'meta_input'    => array()                       
        );
        $id = wp_insert_post( array_merge( $defaults, $data ), true );
        return $id;
    }

    /**
     * Метод обновляет сущность БД
     * @param Entity    $entity     Объект сущности
     * @return bool
     */
    static public function updateEntity( $entity )
    {
        // Удаляем объект и список из кэша
        self::deleteCache( $entity->ID, get_class( $entity ) );
        // Обновляем сущность
        return wp_update_post( $entity ) && update_post_meta( $entity->ID, '', $entity->meta );
    }

    /**
     * Метод удаляет сущность из БД
     * @param Entity    $entity     Объект сущности
     * @return bool
     */
    static public function deleteEntity( $entity )
    {
        self::deleteCache( $entity->ID, get_class( $entity ) );
        return wp_delete_post( $id, true );
    } 

    /* ------------------ Свойства и методы сущности ------------------ */

    /**
     * Объект WP_Post
     * @var WP_Post
     */
    protected $post;


    /**
     * Все мета-поля сущности
     */
    public $meta = array();

    /**
     * Конструктор объекта сущности
     * Создает новый объект сущности CPM по ID или слагу
     * @param int|string $id идентификатор сущности
     */
    public function __construct( $id )
    {
        // В зависимости от типа переданного ID
        if ( is_numeric( $id ) ) {
            // Получение сущности по ID
            $this->post = get_post( $id );
            if ( ! $this->post ) {
                throw new Exception( 
                    __( 'Object' , CPM ) . ' ' . 
                    $id . ' ' . 
                    __( 'not found' , CPM) );
            } 
        } 
        else {
            // Получение сущности по слагу
            $posts = get_posts( array(
                'post_type' => self::CPT,   // Тип сущности
                'posts_per_page' => 1,      // Один объект
                'name' => $id               // Слаг
            ) );
            if ( count( $posts ) === 0 ) {
                throw new Exception( 
                    __( 'Object' , CPM ) . ' ' . 
                    $id . ' ' . 
                    __( 'not found' , CPM) );
            }
            $this->post = $posts[0];           
        }

        // Получем все мета-поля
        $this->meta = get_post_meta( $this->post->ID, '', false );
    }

    /**
     * Метод обновляет объект в БД
     * Может быть перекрыт в потомках
     */
    public function update()
    {
        return self::updateEntity( $this->post );
    }

    /**
     * Метод удаляет объект из БД
     * Может быть перекрыт в потомках
     */
    public function delete()
    {
        return self::deleteEntity( $this->ID );
    }

    /* --------------- Доступ к свойствам сущности --------------- */

    /**
     * Метод возвращает массив свойств сущности и их соответствия со свойствами WP_Post
     * Если свойство сущности отсутствует в этом массиве, то оно будет храниться с мета-полях.
     * Метод должен перекрываться в потомках при необходимости
     * @return array
     */
    protected function get_post_properties()
    {
        return array(
            'ID'            => 'ID',
            'title'         => 'post_title',
            'content'       => 'post_content',
            'slug'          => 'post_name',
            'author'        => 'post_author',
            'created'       => 'post_date',
            'modified'      => 'post_modified',
            'parent'        => 'post_parent',
            'order'         => 'menu_order',
            'type'          => 'post_type',
            'comment_count' => 'comment_count'
        );
    }

    /**
     * Метод возвращает массив свойств сущности и их соответствия с предопределенными мета-полями
     * Если свойство сущности отсутствует в этом массиве, то оно будет храниться с мета-полях со своим именем.
     * Для обратной совместимости
     * Метод должен перекрываться в потомках при необходимости
     * @return array
     */
    protected function get_meta_properties()
    {
        return array(
        );
    }

    /**
     * Магический метод для доступа к свойству сущности
     * @param string    $name   имя свойства
     * @return mixed
     */
    public function __get( $name )
    {
        // Предопределенные поля WP_Post
        $internal_properties = $this->get_post_properties();
        if ( array_key_exists( $name, $internal_properties ) ) {
            return $this->post->{$internal_properties[ $name ]};
        }

        // Предопределенные мета-поля
        $meta_properties = $this->get_meta_properties();
        if ( array_key_exists( $name, $meta_properties ) ) {
            return $this->meta[ $meta_properties[ $name ] ];
        }

        // Читаем остальные мета-поля
        if ( array_key_exists( $name, $this->meta ) ) {
            return $this->meta[$name];
        }

        if ( WP_DEBUG ) {
            $trace = debug_backtrace();
            trigger_error(
                'Undefined property via __get(): ' . $name .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'],
                E_USER_NOTICE);            
        }
        return null;
    }

    /**
     * Магический метод для записи свойства сущности
     * @param string    $name   имя свойства
     * @param mixed     $value  значение свойства
     */
    public function __set( $name, $value )
    {
        // Предопределенные поля WP_Post
        $internal_properties = $this->get_post_properties();
        if ( array_key_exists( $name, $internal_properties ) ) {
            $this->post->{$internal_properties[ $name ]} = $value;
        }

        // Предопределенные мета-поля
        $meta_properties = $this->get_meta_properties();
        if ( array_key_exists( $name, $meta_properties ) ) {
            $this->meta[ $meta_properties[ $name ] ] = $value;
        }

        $this->meta[ $name ] = $value;
    }
}