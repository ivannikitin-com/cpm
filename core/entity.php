<?php
/**
 * Базовый класс сущности CPM
 * Реализует механизм чтения и записи в БД объектов
 */
namespace CPM\Core;

class Entity extends Item {
    /**
     * Тип сущности
     * Должно быть переопределено в наследнике
     */
    static public $CPT = 'cpm_entity';

    /**
     * Название сущности
     * @var string
     */
    public $title;

    /**
     * Описание сущности
     * @var string
     */
    public $description;

    /**
     * Родительская сущность
     * @var int
     */
    public $parent;

    /**
     * Автор
     * @var int
     */
    public $author;

    /**
     * Слаг сущности
     * @var string
     */
    public $slug;


    // --------------- Публичные свойства сущности ---------------- //

    /**
     * Метод возвращает список публичных свойств сущности
     * с помощью рефлексии
     * @static
     * @return mixed
     */
    protected static function get_properties() {
        $reflect = new \ReflectionClass( static::class );
        $props   = $reflect->getProperties( \ReflectionProperty::IS_PUBLIC );
        $prop_names = array();
        foreach ($props as $prop) {
            $prop_names[] = $prop->getName();
        }        
        return $prop_names;
    }

    /**
     * Метод записывает свойства сущности в объект
     * Необходим для реализации обратной совместимости
     * с старыми версиями CPM и правильного маппинга
     * свойств на текущую версию.
     * 
     * @param mixed $properties Массив свойств
     */
    public function set_properties( $properties ) {
        // Читаем свойства сущности и каждую пытаемся 
        // установить в объекте
        foreach ( static::get_properties() as $property) {
            if ( array_key_exists( $property, $properties ) ) {
                $this->$property = $properties[$property];
            }
        }
    }

    /**
     * Метод возвращает список публичных свойств сущности,
     * которые НЕ СЛЕДУЕТ сохранять в мета-поля стандартным
     * API WordPress. Эти поля должны напрямую обрабатываться
     * в методе save()
     * 
     * @return array
     */
    protected static function get_meta_except_properties() {
        return array(
            // Поля, которые сохраняются непосредственно в объекте CPT
            'id',               // Идентификатор
            'title',            // Заголовок
            'description',      // Описание
            'parent',           // Родитель
            'author',           // Автор
            'slug'              // Слаг
        );
    }

    // ----------------- Методы чтения и записи ------------------- //

    /**
     * Метод загружает сущность из БД, в том случае, 
     * если ID не нулевой 
     */
    public function load() {
        if ( empty( $this->id ) ) return; // ID не задано, ничего не делаем

        // Загружаем из БД
        $post = get_post( $this->id );
        if ( ! $post ) {
            throw new ItemNotFoundException( 'Object ' . static::class . ' not found', $this->id );
        }

        // Заполняем свойства объекта
        $this->title = $post->post_title;
        $this->description = $post->post_content;
        $this->parent = $post->post_parent;
        $this->author = $post->post_author;
        $this->slug = $post->post_name;

        // Читаем метаданные WordPress post
        $meta_fields = array(); 
        foreach( get_post_meta( $this->id ) as $meta_field => $meta_value ) {
            if ( isset($meta_value[0] ) ) {
                $meta_fields[$meta_field] = $meta_value[0];
            }
            
        };
        // Записываем мета-поля в объект
        $this->set_properties( $meta_fields );

        // Кэшируем объект
        $this->save_to_cache();
    }

    /**
     * Метод сохраняет сущность в БД
     */
    public function save() {

        // Свойства записи
        $post = array(
            'post_type'     => static::CPT,
            'post_title'    => $this->title,
            'post_content'  => $this->description,
            'post_parent'   => $this->parent,
            'post_author'   => $this->author,
            'post_name'     => $this->slug,
            'post_status'   => 'publish',
        );

        if ( empty( $this->id ) ) {
            // Новая запись
            $post[ 'post_author' ] = get_current_user_id();
            $this->id = wp_insert_post( $post );            
        }
        else {
            // Обновление
            $post[ 'ID' ] = $this->id;
            wp_update_post( $post );
        }

        // Записываем мета-поля
        $exceptions = static::get_meta_except_properties();
        foreach ( static::get_properties() as $property ) {
            if ( in_array( $property, $exceptions ) ) {
                // Поле не сохраняется в мета свойство
                continue;
            }
            update_post_meta( $this->id, $property, $this->$property );
        }

        // Очищаем кэш
        static::deleteCache( $this->id );
    }

    /**
     * Метод удаляет сущность из БД
     */
    public function delete() {
        if ( empty( $this->id ) ) return; // ID не задано, ничего не делаем

        // Удаляем из БД
        wp_delete_post( $this->id, true );
        
        // Удаляем из кэша
        static::deleteCache( $this->id );
    }
}