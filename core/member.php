<?php
/**
 * Класс Member
 * Участник сущности
 */
namespace CPM\Core;

class Member extends Item {
    /**
     * Инициализация участников, необходима для управления кэшированием
     * @static
     */
    static function init() {
        // При изменении пользователя WordPress очистка кэша
        // Идентификатор пользователя передается первым параметром
        // LINK: https://developer.wordpress.org/reference/hooks/wp_update_user/
        add_action( 'wp_update_user', array( '/CPM/Core/Item', 'delete_cache' ) );
        add_action( 'delete_user',    array( '/CPM/Core/Item', 'delete_cache' ) );
    }

    /**
     * @var string Имя участника
     */
    public $name;

    /**
     * @var string E-mail
     */
    public $email;

    /**
     * @var string URL аватара участника
     */
    public $avatar;

    // TODO: Продумать роли!
    /**
     * @var string Роль участника
     */
    public $role;

    /**
     * Метод загружает участника из БД, если ID не нулевой 
     * или инициализирующий участника, если ID нулевой
     */
    public function load() {
        // Если ID ноль, ничего не делаем
        if ( $this->id === 0 ) {
            return;
        }

        // Запрос участника из базы данных 
        $wp_user = get_user_by( 'id', $this->id );
        if ( ! $wp_user) {
            /CPM/Plugin::get_instance()->log( 'Member not found by id: ' . $id, 'error' );
            throw new ItemNotFoundException( 'Пользователь не найден' );
        }

        // Данные пользователя
        $this->name = $wp_user->user_login;
        $this->email = $wp_user->user_email;
        $this->avatar = $wp_user->user_avatar;
        $this->role = $wp_user->roles[0];
    }   
}