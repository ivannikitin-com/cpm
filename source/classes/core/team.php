<?php
/**
 * Class Team.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Участники сущности. Не проверяет права.
 *
 * Спецификация: docs/core/team.md, docs/core/права-и-роли.md
 */
class Team {

	/**
	 * Ассоциативный массив user_id => role.
	 *
	 * @var array
	 */
	public $members = array();

	/**
	 * Разбирает строку members формата `"12":manager,"15":co_worker`.
	 *
	 * @param string $members Строка участников из мета-поля `_team`.
	 */
	public function __construct( $members = '' ) {
		if ( empty( $members ) || ! is_string( $members ) ) {
			return;
		}

		if ( preg_match_all( '/"(\d+)":([^,]+)/', $members, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$user_id = (int) $match[1];
				$role    = $match[2];
				$this->members[ $user_id ] = $role;
			}
		}
	}

	/**
	 * Добавляет пользователя и роль в members.
	 *
	 * @param int    $user_id ID пользователя.
	 * @param string $role    Роль из констант ACL.
	 */
	public function add( $user_id, $role ) {
		$this->members[ (int) $user_id ] = $role;
	}

	/**
	 * Удаляет пользователя из members. Если не найден — ничего не делает.
	 *
	 * @param int $user_id ID пользователя.
	 */
	public function remove( $user_id ) {
		unset( $this->members[ (int) $user_id ] );
	}

	/**
	 * Каноничная строка для сохранения в мета-поле `_team`.
	 *
	 * Роли administrator и none в БД не сохраняются.
	 *
	 * @return string
	 */
	public function get_members() {
		$parts = array();
		foreach ( $this->members as $user_id => $role ) {
			if ( ACL::ADMINISTRATOR === $role || ACL::NO_ROLE === $role ) {
				continue;
			}
			$parts[] = '"' . (int) $user_id . '":' . $role;
		}
		return implode( ',', $parts );
	}
}
