<?php
/**
 * Class ACL (Access Control List).
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Хранит логику ролей пользователей и вычисляет проекты, доступные пользователю.
 *
 * Класс полностью статический. Спецификация: docs/core/acl.md.
 * Реализация get_role()/get_user_projects() — по мере разработки ядра.
 */
class ACL {

	const ADMINISTRATOR = 'administrator';
	const MANAGER       = 'manager';
	const CO_WORKER     = 'co_worker';
	const CLIENT        = 'client';
	const NO_ROLE       = 'none';

	/**
	 * Возвращает роль пользователя для указанной сущности.
	 *
	 * @param \CPM\v3\Core\Entity $entity  Сущность.
	 * @param int|null            $user_id ID пользователя (по умолчанию текущий).
	 * @return string Одна из констант ролей ACL.
	 */
	public static function get_role( $entity, $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		return self::NO_ROLE;
	}

	/**
	 * Возвращает массив ID проектов, доступных пользователю.
	 *
	 * @param int $user_id ID пользователя.
	 * @return int[]
	 */
	public static function get_user_projects( $user_id ) {
		return array();
	}

	/**
	 * Сбрасывает кэш вычисленных ID проектов для пользователей.
	 */
	public static function flush() {
	}
}
