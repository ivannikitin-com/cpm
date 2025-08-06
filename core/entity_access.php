<?php
/**
 * Класс реализует логику проверки прав доступа к сущности при выполнении операций
 *    - Read
 *    - Save
 *    - Delete
 */
namespace CPM\Core;

class EntityAccess extends EntityList {
    /**
     * Участники сущности
     * Массив объектов Member
     * @var array
     */
    public $members;

    /**
     * Поля, чтение и сохранение которых реализуется здесь своим образом
     * @return array
     */
    protected static function get_meta_except_properties() {
        return array_merge( parent::get_meta_except_properties(), array( 'members' ) );
    }

    /**
     * Метод загружает сущность из БД, в том случае, если ID не нулевой 
     */
    public function load() {
        if ( empty( $this->id ) ) return; // ID не задано, ничего не делаем

        // Загружаем из БД
        $this->members = static::load_members( $this->id );

        // Проверяем права доступа к сущности
        $user_id = get_current_user_id();
        if ( ! static::check_access( $this->id, $user_id, 'read', $this->members ) ) {
            throw new EntityAccessException( 'User ' . $user_id . ' has no access to entity', $this->id );
        }

        // Выполняем действия чтения родительского класса в конце
        // так как в нем происходит кэширование прочитанного объекта
        parent::load();
    }

    /**
     * Метод сохраняет сущность в БД
     */
    public function save() {
        // Проверяем права доступа к сущности
        $user_id = get_current_user_id();
        if ( ! static::check_access( $this->id, $user_id, 'write', $this->members ) ) {
            throw new EntityAccessException( 'User ' . $user_id . ' has no access to entity', $this->id );
        }        

        // Выполняем действия сохранения родительского класса в начале
        // так как в нем происходит может происходить создание новой записи в БД
        // и назначение свойства $this->id
        // Кэширование не требуется, так как в родительском методе происходит
        // удаление объекта сущности из кэша
        parent::save();

        // Сохраняем участников сущности
        static::save_members( $this->id, $this->members );
    }

    /**
     * Метод удаляет сущность из БД
     */
    public function delete() {
        // Проверяем права доступа к сущности
        $user_id = get_current_user_id();
        if ( ! static::check_access( $this->id, $user_id, 'delete', $this->members ) ) {
            throw new EntityAccessException( 'User ' . $user_id . ' has no access to entity', $this->id );
        } 


        // Выполняем действия удаления родительского класса в конце 
        // так как в нем происходит удаление объекта сущности из БД     
        parent::delete();
    }

    // --------------- Методы чтения и сохранения участников сущности ----------------- //
    /*  Методы вынесены отдельно, для реализации в каждом классе обратной совместимости
     *  со старым способом чтения участников сущности.
     *  Методы статичные, чтобы можно было выполнить загрузку данных о пользователях
     *  и их правах до загрузки объекта, например, при вызовет REST API
     */
    
    /**
     * Метод загружает участников сущности по ID 
     * @static
     * @return array
     */
    public static function load_members( $entity_id ) {
        /* Стандартный способ хранения участников сущности
         * в мета-поле -- сериализованный массив объектов
         */
        try {
            return unserialize( get_post_meta( $entity_id, 'members', true ) );
        } catch ( Exception $e ) {
            \CPM\Plugin::get_instance()->log( 'Unserialize members error for ' . static::class . '  id: ' . $entity_id, 'warning' );
            return array();
        }
    }

    /**
     * Метод сохраняет участников сущности по ID
     * Метод статичный для единообразия
     * @static
     */
    public static function save_members( $entity_id, $members ) {
        /* Стандартный способ хранения участников сущности
         * в мета-поле -- сериализованный массив объектов
         */
        update_post_meta( $entity_id, 'members', serialize( $members ) );
    }

    /**
     * Метод возвращает участников сущности по ID объекта
     * при условии, если у текущего объекта нет участников, 
     * родительский объект и его участники возвращаются.
     * И так далее рекурсивно, пока родительский объект будет иметь ID == 0
     * Метод статичный, так используется для проверок в REST API, 
     * чтобы было возможность получить участников до создания объекта сущности
     * @static
     * @param int $entity_id    ID сущности
     * @param array $members    Список участников, передаем его, если он известен, чтобы не загружать повторно
     * @return array
     */
    public static function get_members( $entity_id, $members = array() ) {
        // Если список участников не передан, загружаем его из БД
        if ( empty( $members ) ) {
            $members = static::load_members( $entity_id );
        }
        
        // Если список участников пуст, рекурсивно ищем участников у родителя
        if ( empty( $members ) ) {
            $parent_id = wp_get_post_parent_id( $entity_id );
            if ( ! empty( $parent_id ) ) {
                return static::get_members( $parent_id );
            }
        }
        return $members;
    }

    // -------------------------- Проверка прав участника сущности -------------------------- //

    /**
     * Функция проверяет имеет ли право пользователь с указанным ID право на выполнение операции
     * @static
     * @param int $entity_id    ID сущности
     * @param int $user_id      ID пользователя
     * @param string $operation Название операции
     * @param array $members    Список участников, передаем его, если он известен, чтобы не загружать повторно
     * @return bool
     */
    public static function has_access( $entity_id, $user_id, $operation, $members = array() ) {
        // Пользователь не определен, не разрешаем!
        if ( ! $user_id ) return false;

        // Получаем актуальный список участников
        $members = static::get_members( $entity_id, $members );

        // Найдем пользователя...
        foreach ( $members as $member ) {
            if ( $member->id == $user_id ) {
                // Список разрешений
                $permissions = static::get_permissions();

                // Если роль не указана, не разрешаем!
                if ( ! isset( $permissions[$member->role] ) ) return false;

                // Если операции нет, не разрешаем!
                if ( ! isset( $permissions[$member->role][$operation] ) ) return false;

                // Возвращает разрешение
                return $permissions[$member->role][$operation];
            }
        }

        // Если пользователя нет в списке участников, не разрешаем!
        return false;
    }

    /**
     * Функция возвращает разрешения для сущности с указанными и ролям и операциями
     * Метод перекрывается потомками и, возможно, настройками через 
     * вызов фильтра cpm_entity_access_permissions (permissions, entity_class)
     * @static
     * @return array
     */
    public static function get_permissions() {
        return apply_filters( 'cpm_entity_access_permissions',  array(
            'administrator' => array(
                'read'   => true,
                'write'  => true,
                'delete' => true
            ),
            'employee'      => array(
                'read'   => true,
                'write'  => true,
                'delete' => false
            ),
            'lumper'        => array(
                'read'   => true,
                'write'  => false,
                'delete' => false
            ),
            'agent'         => array(
                'read'   => true,
                'write'  => false,
                'delete' => false
            ),
            'customer'      => array(
                'read'   => true,
                'write'  => false,
                'delete' => false
            )
        ), static::class );
    }
}