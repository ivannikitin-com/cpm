<?php
/**
 * Class Note.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Записка / документ проекта.
 *
 * Спецификация: docs/core/note.md
 */
class Note extends Project_Entity {

	const CPT = 'cpm_docs';

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
	project.id AS project_id,
	project.title AS project_title,
	project.slug AS project_slug,
	MAX(CASE WHEN pm.meta_key = '_doc_type' THEN pm.meta_value ELSE NULL END) AS doc_type
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
	p.post_type = 'cpm_docs'
GROUP BY
	p.ID
HAVING
	TRUE
";

	/**
	 * Тип заметки: _custom_doc / _google_doc.
	 *
	 * @var string
	 */
	public $doc_type = '_custom_doc';

	/**
	 * @param array $args Данные сущности, включая doc_type.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		$this->doc_type = isset( $args['doc_type'] ) ? (string) $args['doc_type'] : '_custom_doc';
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
						'_doc_type'         => $this->doc_type,
						'_project_uploaded' => $this->project_id ? $this->project_id : $this->parent,
					)
				),
			)
		);
	}
}
