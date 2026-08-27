<?php
/**
 * Class Message.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Обсуждение в проекте.
 *
 * Спецификация: docs/core/message.md
 */
class Message extends Project_Entity {

	const CPT = 'cpm_message';

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
	MAX(CASE WHEN pm.meta_key = '_milestone' THEN pm.meta_value ELSE NULL END) AS milestone,
	MAX(CASE WHEN pm.meta_key = '_message_privacy' THEN pm.meta_value ELSE NULL END) AS message_privacy
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
	p.post_type = 'cpm_message'
GROUP BY
	p.ID
HAVING
	TRUE
";

	/**
	 * Приватность обсуждения: yes / no.
	 *
	 * @var string
	 */
	public $message_privacy = 'no';

	/**
	 * ID вехи или 0, если не назначена.
	 *
	 * @var int
	 */
	public $milestone = 0;

	/**
	 * ID вложений. Не пишется через get_wp_args() основной выборки.
	 *
	 * @var array
	 */
	public $files = array();

	/**
	 * @param array $args Данные сущности.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		$this->message_privacy = isset( $args['message_privacy'] ) ? (string) $args['message_privacy'] : 'no';
		$this->milestone       = isset( $args['milestone'] ) ? (int) $args['milestone'] : 0;
		$this->files           = isset( $args['files'] ) && is_array( $args['files'] ) ? $args['files'] : array();
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
						'_message_privacy' => $this->message_privacy,
						'_milestone'       => $this->milestone,
					)
				),
			)
		);
	}
}
