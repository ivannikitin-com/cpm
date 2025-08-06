<?php
/**
 * Класс Item
 * Базовый класс для объектов CPM, которые хранятся в БД
 * Реализует механизм кэширования на уровне объекта
 */
namespace CPM\Core;

abstract class Item {
    /**
     * Инициализация класса
     * @static
     */
    public static function init() {
        // Метод должен перекрываться потомками
    }

    /**
     * @var int Идентификатор элемента
     */
    public $id;

    /**
     * Конструктор
     * Получает и сохраняет идентификатор элемента
     *     - Если ID не нулевой, загружает элемент из кэша
     *     - Если ID нулевой, просто создает объект
     * @param int $id Идентификатор элемента
     */
    public function __construct( int $id = 0 ) {
        $this->id = $id;
        if ( $id !== 0) {
            // Поиск объекта в кэше
            $cached_item = $this->get_from_cache( $id );
            if ( $cached_item ) {
                // Копируем свойства из кэшированного объекта
                foreach (get_object_vars($cached_item) as $key => $value) {
                    $this->$key = $value;
                }
            }
            else {
                // Загрузка объекта
                $this->load();
                // Сохранение объекта в кэш
                $this->save_to_cache();
            }
        }     
    }

    /**
     * Метод загружает элемент из БД, если ID не нулевой 
     * или инициализирующий элемент, если ID нулевой
     * @return void
     */
    abstract public function load();

    /**
     * Возвращает ключ элемента в кэше
     * @static
     * @param int $id Идентификатор элемента
     */
    static function get_cache_key( $id ) {
        return strtolower( str_replace( '\\', '_', static::class ) ) . '_' . $id;
    }

    /**
     * Метод возвращает объект из кэша
     */
    protected function get_from_cache() {
        $cached_item = wp_cache_get( static::get_cache_key( $this->id ), CPM );
        if ( $cached_item ) {
            \CPM\Plugin::get_instance()->log( 'Item ' . static::class . ' found in cache by id: ' . $id, 'info' );
        }
        return $cached_item;
    }

    /**
     * Метод сохраняет объект в кэш
     */
    protected function save_to_cache() {
        wp_cache_set( static::get_cache_key( $this->id ), $this, CPM );
        \CPM\Plugin::get_instance()->log( 'Item ' . static::class . ' saved in cache by id: ' . $id, 'info' );
    }

    /**
     * Удаление элемента из кэша
     * @static
     * @param int $id Идентификатор элемента
     */
    static function delete_cache( $id ) {
        wp_cache_delete( self::get_cache_key( $id ), CPM );
        \CPM\Plugin::get_instance()->log( 'Item deleted from cache by id: ' . $id, 'info' );
    }
}