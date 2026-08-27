<?php
/**
 * Class User.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Пользователь CPM. Не записывает данные в БД.
 *
 * Спецификация: docs/core/user.md
 */
class User {

	/**
	 * ID пользователя WordPress.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Роль пользователя (значения констант ACL).
	 *
	 * @var string
	 */
	public $role;

	/**
	 * Отображаемое имя (display_name). Пусто до вызова load().
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * URL аватара. Пусто до вызова load().
	 *
	 * @var string
	 */
	public $avatar = '';

	/**
	 * Заполняет обязательные поля. Не вызывает load().
	 *
	 * @param int    $id   ID пользователя WordPress.
	 * @param string $role Роль из констант ACL.
	 */
	public function __construct( $id, $role ) {
		$this->id   = (int) $id;
		$this->role = $role;
	}

	/**
	 * Возвращает имя пользователя. При пустом name вызывает load().
	 *
	 * @return string
	 */
	public function get_name() {
		if ( '' === $this->name ) {
			$this->load();
		}
		return $this->name;
	}

	/**
	 * Возвращает URL аватара. При пустом avatar вызывает load().
	 *
	 * @return string
	 */
	public function get_avatar() {
		if ( '' === $this->avatar ) {
			$this->load();
		}
		return $this->avatar;
	}

	/**
	 * Читает name и avatar из WP_User. Если пользователь не найден — BadUserException.
	 *
	 * @throws BadUserException Пользователь с указанным id не найден.
	 */
	protected function load() {
		$user = get_userdata( $this->id );
		if ( ! $user || is_wp_error( $user ) ) {
			throw new BadUserException( 'User not found: ' . $this->id );
		}

		$this->name   = $user->display_name;
		$this->avatar = get_avatar_url( $this->id );
	}
}
