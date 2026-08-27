<?php
/**
 * Class Milestone.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Веха проекта.
 *
 * Спецификация: docs/core/milestone.md
 */
class Milestone extends Project_Entity {

	const CPT = 'cpm_milestone';

	/**
	 * Шаблон SQL выборки. Плейсхолдеры подставляет Core_Manager::load_list().
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
	MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END) AS team,
	project.id AS project_id,
	project.title AS project_title,
	project.slug AS project_slug,
	MAX(CASE WHEN pm.meta_key = '_due' THEN pm.meta_value ELSE NULL END) AS due,
	MAX(CASE WHEN pm.meta_key = '_completed' THEN pm.meta_value ELSE NULL END) AS completed
FROM
	{posts} p
		INNER JOIN {postmeta} pm
			ON p.ID = pm.post_id
		INNER JOIN (
			SELECT DISTINCT id, title, slug FROM (
				SELECT
					p.ID AS id,
					p.post_title AS title,
					p.post_name AS slug,
					COALESCE(
						MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
						(SELECT GROUP_CONCAT(CONCAT('\"', user_id, '\":', `role`)) FROM {cpm_user_role} r WHERE project_id = p.ID GROUP BY project_id)
					) AS team
				FROM {posts} p
					INNER JOIN {postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = 'cpm_project'
				GROUP BY p.ID
			) projects
			WHERE ( {is_admin} OR team LIKE {team_like} )
		) project
			ON p.post_parent = project.id
WHERE
	p.post_type = 'cpm_milestone'
GROUP BY
	p.ID
HAVING
	TRUE
";

	/**
	 * Срок вехи или пустая строка.
	 *
	 * @var string
	 */
	public $due = '';

	/**
	 * Флаг завершения: 0 / 1.
	 *
	 * @var int
	 */
	public $completed = 0;

	/**
	 * @param array $args Данные сущности, включая due и completed.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		$this->due       = isset( $args['due'] ) ? (string) $args['due'] : '';
		$this->completed = isset( $args['completed'] ) ? (int) $args['completed'] : 0;
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
						'_due'       => $this->due,
						'_completed' => $this->completed,
					)
				),
			)
		);
	}
}
