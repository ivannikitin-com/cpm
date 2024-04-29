<?php
/**
 * Базовый класс сущностей CPM
 * Обеспечивает низкоуровенную работу с сущностями CPM
 * @author Ivan Nikitin
 * @package CPM
 * @version 3.0.0
 */

namespace CPM\Core;

class Entity extends \WP_Post
{
    /**
     * Тип сущности
     * Должно быть переопределено в наследнике
     */
    static public $CPT = 'cpm_entity';

    /**
     * Метод возвращает объект сущности CPM из кэша
     * @param int|string    $id     идентификатор сущности
     * @param string        $class  имя класса сущности
     * @return object
     */
    private static function getEntity($id, $class)
    {
        // Поиск сущности в кэше
        $entity_name = $class . '_' . $id;
        $cache = wp_cache_get( $entity_name );
        if ( $cache )
            return $cache;
    
        // Запрашиваем сущность из ее класса
        $entity = $class::get($id);
        wp_cache_add( $entity_name, $entity );
        return $entity;
    }

    /**
     * Метод удаляет объект в кэше
     * @param int|string    $id     идентификатор сущности
     * @param string        $class  имя класса сущности
     */
    static public function deleteCache($id, $class)
    {
        $entity_name = $class . '_' . $id;
        wp_cache_delete( $entity_name );
    }

    /**
     * Метод обновляет сущность БД
     * @param Entity    $entity     Объект сущности
     */
    static public function update($entity)
    {
        self::deleteCache( $entity->ID, get_class( $entity ) );
        return wp_update_post( $entity ) && update_post_meta( $entity->ID, '', $entity->meta );
    }

    /**
     * Метод создает новый объект в БД
     * Возвращает ID созданной сущности
     * При необходимости можно запросить объект через getEntity
     * чтобы он сразу появился в кэше
     * @param mixed     $data   данные для создания сущности
     * @return int
     */
    static public function create( $data )
    {
        $id = wp_insert_post( $data, true );
        return $id;
    }

    /**
     * Метод удаляет сущность из БД
     * @param int|string    $id     идентификатор сущности
     * @return bool
     */
    static public function delete( $id )
    {
        self::deleteCache( $entity->ID, get_class( $entity ) );
        return wp_delete_post( $id, true );
    } 

    /* ----------------------------------------------------------------- */

    /**
     * Все мета-поля сущности
     */
    public $meta = array();

    /**
     * Конструктор объекта сущности
     * Создает новый объект сущности CPM по ID или слагу
     * @param int|string $id идентификатор сущности
     */
    public function __construct($id)
    {
        // В зависимости от типа переданного ID
        if ( is_numeric( $id ) ) {
            // Получение сущности по ID
            $post = get_post( $id );
            if ( ! $post ) {
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
            $post = $posts[0];           
        }

        // Инициализируем WP_Post
        patent::__construct( $post );

        // Получем все мета-поля
        $this->meta = get_post_meta( $post->ID, '', false );
    }

}