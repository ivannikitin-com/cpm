<?php
/**
 * Класс реализует статичные методы чтения списков сущностей напрямую из БД
 */
namespace CPM\Core;

class EntityList extends Entity {   
   /**
     * Метод возвращает SQL запрос прямого чтения списка сущностей
     * из БД. Это необходимо для быстрого чтения списка сущностей
     * без чтения свойств каждой сущности по API WordPress
     * 
     * ВАЖНО: Имена колонок в SQL запросе должны совпадать с именами свойств, 
     * либо необходимо переопределять метод set_properties() 
     * 
     * @static
     * @return string
     */
    protected static function get_list_sql() {
        global $wpdb;
        $cpt = static::CPT;

        $sql = "SELECT
                    ID AS id,
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
                HAVING
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

        // Возвращаемые параметры запроса
        $sql_args = array(
            'WHERE' => array(),
            'ORDER' => null,
            'LIMIT' => null
        );

        // Чтение и поиск известных параметров SQL запроса
        foreach ( $args as $key => $value ) {
            switch ( $key ) {
                case 'id':
                    $sql_args['WHERE'][] = $wpdb->prepare( 'ID = %d',  $value );
                    break;

                case 'author':
                    $sql_args['WHERE'][] = $wpdb->prepare( 'post_author = %s',  $value );
                    break;

                case 'date':
                    $sql_args['WHERE'][] = $wpdb->prepare( 'post_date = %s',  $value );
                    break;

                case 'content':
                    $sql_args['WHERE'][] = $wpdb->prepare( 'post_content LIKE %s', '%'.$value.'%' );
                    break;

                case 'title':
                    $sql_args['WHERE'][] = $wpdb->prepare( 'post_title LIKE %s', '%'.$value.'%' );
                    break;

                case 'slug':
                    $sql_args['WHERE'][] = $wpdb->prepare( 'post_name = %s',  $value );
                    break;

                case 'parent':
                    $sql_args['WHERE'][] = $wpdb->prepare( 'post_name = %d',  $value );
                    break;                    

                case 'orderby':
                    $order_by = $wpdb->prepare( 'ORDER BY %s',  $value );
                    // Примитивная очистка от инъекции
                    $order_by = str_replace(array('\'', ';', '-', '--', 'UNION'), '', $order_by);
                    $sql_args['ORDER'] = $order_by;
                    break;

                case 'limit':
                    $sql_args['LIMIT'] = $wpdb->prepare( 'LIMIT %d',  $value );
                    break;
                }
            }
        return $sql_args;
    }

    /**
     * Подготовка SQL запроса
     * @static
     * @param string   $sql      SQL запрос
     * @param mixed    $sql_args Массив параметров запроса
     * @return string
     */
    protected static function prepare_sql( $sql, $sql_args )
    {
        // Подстановка дополнительных параметров в запрос
        if ( isset( $sql_args['WHERE'] ) && count( $sql_args['WHERE'] ) > 0 ) $sql = str_replace( 
            '-- EXTRA_WHERE --', 
            ' AND ' . implode( ' AND ', $sql_args['WHERE'] ), 
            $sql 
        );
        if ( isset( $sql_args['ORDER'] ) ) $sql = str_replace( '-- ORDER --', $sql_args['ORDER'], $sql );
        if ( isset( $sql_args['LIMIT'] ) ) $sql = str_replace( '-- LIMIT --', $sql_args['LIMIT'], $sql );

        return $sql;
    }    

    /**
     * Метод считывает массив объектов из БД
     * @static
     * @param array    $args     Параметры запроса
     */
    public static function load_list( $args=array() ) {
        global $wpdb;

        // Параметры запроса
        $sql = static::prepare_sql( static::get_sql(), static::get_sql_args( $args ) );

        // Выполнение запроса
        $posts = $wpdb->get_results( $sql, ARRAY_A );
        
        // Создание массива сущностей
        $entities = array();
        foreach ( $posts as $post ) {
            $entity = new static( 0 ); // Передаем 0, чтобы не выполнять загрузку
            
            // Инициализация публичных свойств сущности
            $entity->set_properties( $post );
          
            $entities[] = $entity;
        }
        return $entities;
    }
}