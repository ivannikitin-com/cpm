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
 */
class ACL {

	const ADMINISTRATOR = 'administrator';
	const MANAGER       = 'manager';
	const CO_WORKER     = 'co_worker';
	const CLIENT        = 'client';
	const NO_ROLE       = 'none';

	/**
	 * Группа объектного кэша WordPress для прав CPM.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'cpm';

	/**
	 * Возвращает роль пользователя для указанной сущности.
	 *
	 * @param \CPM\v3\Core\IEntity $entity  Сущность.
	 * @param int|null             $user_id ID пользователя (по умолчанию текущий).
	 * @return string Одна из констант ролей ACL.
	 */
	public static function get_role( $entity, $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$user_id = (int) $user_id;

		if ( user_can( $user_id, 'manage_options' ) ) {
			return self::ADMINISTRATOR;
		}

		if ( empty( $entity ) ) {
			return self::NO_ROLE;
		}

		$project = $entity->get_project();
		if ( empty( $project ) ) {
			return self::NO_ROLE;
		}

		if ( isset( $project->team ) && array_key_exists( $user_id, $project->team->members ) ) {
			return $project->team->members[ $user_id ];
		}

		return self::NO_ROLE;
	}

	/**
	 * Шаблон SQL списка проектов пользователя.
	 * Плейсхолдеры {posts}, {postmeta}, {cpm_user_role}, {team_like}.
	 *
	 * @var string
	 */
	public static $SQL = "
SELECT
	p.ID AS id,
	COALESCE(
		MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
		(SELECT GROUP_CONCAT(CONCAT('\"', user_id, '\":', `role`)) FROM {cpm_user_role} r WHERE project_id = p.ID GROUP BY project_id)
	) AS team
FROM
	{posts} p
		INNER JOIN {postmeta} pm
			ON p.ID = pm.post_id
WHERE
	p.post_type = 'cpm_project'
GROUP BY
	p.ID
HAVING
	team LIKE {team_like}
ORDER BY
	p.ID ASC
";

	/**
	 * Возвращает массив ID проектов, доступных пользователю.
	 *
	 * Приоритет нового формата `_team` над легаси `cpm_user_role` через COALESCE.
	 *
	 * @param int $user_id ID пользователя.
	 * @return int[]
	 */
	public static function get_user_projects( $user_id ) {
		$user_id   = (int) $user_id;
		$cache_key = 'cpm_acl_user_' . $user_id;
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$sql = str_replace(
			array( '{posts}', '{postmeta}', '{cpm_user_role}' ),
			array( $wpdb->posts, $wpdb->postmeta, $wpdb->prefix . 'cpm_user_role' ),
			self::$SQL
		);
		$like = $wpdb->prepare( '%s', '%"' . $user_id . '"%' );
		$sql  = str_replace( '{team_like}', $like, $sql );

		$ids = $wpdb->get_col( $sql );
		if ( empty( $ids ) ) {
			$ids = array();
		}
		$ids = array_map( 'intval', $ids );

		wp_cache_set( $cache_key, $ids, self::CACHE_GROUP );
		return $ids;
	}

	/**
	 * Сбрасывает кэш вычисленных ID проектов для пользователей.
	 */
	public static function flush() {
		wp_cache_flush_group( self::CACHE_GROUP );
	}
}
