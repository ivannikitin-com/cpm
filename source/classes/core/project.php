<?php
/**
 * Class Project.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Проект CPM.
 *
 * Спецификация: docs/core/project.md
 */
class Project extends Entity {

	const CPT = 'cpm_project';

	/**
	 * Шаблон SQL выборки. Плейсхолдеры {posts}, {postmeta}, {cpm_user_role}
	 * подставляет Core_Manager::load_list().
	 *
	 * @var string
	 */
	public static $SQL = "
SELECT
	p.ID AS id,
	p.post_parent AS parent,
	p.post_author AS author,
	p.post_title AS title,
	p.post_content AS content,
	p.post_date AS created_at,
	p.post_name AS slug,
	p.menu_order,
	MAX(CASE WHEN pm.meta_key = '_thumbnail_id' THEN pm.meta_value ELSE NULL END) AS thumbnail_id,
	COALESCE(
		MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
		(SELECT GROUP_CONCAT(CONCAT('\"', user_id, '\":', `role`)) FROM {cpm_user_role} r WHERE project_id = p.ID GROUP BY project_id)
	) AS team,
	MAX(CASE WHEN pm.meta_key = '_cpm_coordinator' THEN pm.meta_value ELSE NULL END) AS coordinator,
	MAX(CASE WHEN pm.meta_key = '_project_active' THEN pm.meta_value ELSE NULL END) AS active
FROM
	{posts} p
		INNER JOIN {postmeta} pm
			ON p.ID = pm.post_id
WHERE
	p.post_type = 'cpm_project'
GROUP BY
	p.ID
HAVING
	TRUE
	AND ( {is_admin} OR team LIKE {team_like} )
ORDER BY
	p.menu_order DESC,
	p.post_title ASC
";

	/**
	 * ID координатора проекта (Product Owner).
	 *
	 * @var int
	 */
	public $coordinator = 0;

	/**
	 * Статус проекта: yes — активный, no — архивный.
	 *
	 * @var string
	 */
	public $active = 'yes';

	/**
	 * @param array $args Данные сущности, включая coordinator и active.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		$this->coordinator = isset( $args['coordinator'] ) ? (int) $args['coordinator'] : 0;
		$this->active      = isset( $args['active'] ) ? (string) $args['active'] : 'yes';
	}

	/**
	 * Проект принадлежит сам себе.
	 *
	 * @return Project
	 */
	public function get_project() {
		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_wp_args() {
		$args = parent::get_wp_args();
		return array_merge(
			$args,
			array(
				'post_type'  => static::CPT,
				'meta_input' => array_merge(
					$args['meta_input'],
					array(
						'_cpm_coordinator' => $this->coordinator,
						'_project_active'  => $this->active,
					)
				),
			)
		);
	}

	/**
	 * Устанавливает координатора и сохраняет проект.
	 *
	 * @param int $user_id ID пользователя WordPress.
	 */
	public function set_coordinator( $user_id ) {
		$this->coordinator = (int) $user_id;
		$this->save();
	}

	/**
	 * @return bool
	 */
	public function is_active() {
		return 'yes' === $this->active;
	}

	/**
	 * Помечает проект как архивный.
	 */
	public function archive() {
		$this->active = 'no';
		$this->save();
	}

	/**
	 * Помечает проект как активный.
	 */
	public function unarchive() {
		$this->active = 'yes';
		$this->save();
	}

	/**
	 * Удаление проекта доступно только администратору WordPress.
	 *
	 * @param bool $is_recursion Вложенный шаг рекурсии.
	 * @throws AccessDeniedException Текущий пользователь не администратор.
	 */
	public function delete( $is_recursion = false ) {
		if ( ACL::ADMINISTRATOR !== ACL::get_role( $this ) ) {
			throw new AccessDeniedException( 'Only administrator can delete a project' );
		}
		parent::delete( $is_recursion );
	}

	/**
	 * Удаляет запись проекта и легаси-строки in_cpm_user_role.
	 */
	protected function delete_entity() {
		parent::delete_entity();

		global $wpdb;
		$table = $wpdb->prefix . 'cpm_user_role';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE project_id = %d",
				$this->id
			)
		);
	}
}
