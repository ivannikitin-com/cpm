<?php
/**
 * Class Task.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Задача в списке задач проекта.
 *
 * Спецификация: docs/core/task.md
 */
class Task extends Project_Entity {

	const CPT = 'cpm_task';

	/**
	 * Шаблон SQL выборки. Плейсхолдеры подставляет Core_Manager::load_list().
	 *
	 * Основной случай: parent = список задач. Редкие parent = проект/задача
	 * обрабатываются в get_project() при чтении единичной задачи.
	 *
	 * @var string
	 */
	public static $SQL = "
SELECT
	p.ID AS id,
	MAX(p.post_parent) AS parent,
	MAX(p.post_author) AS author,
	MAX(p.post_title) AS title,
	MAX(p.post_content) AS content,
	MAX(p.post_date) AS created_at,
	MAX(p.post_name) AS slug,
	MAX(p.menu_order) AS menu_order,
	MAX(CASE WHEN pm.meta_key = '_thumbnail_id' THEN pm.meta_value ELSE NULL END) AS thumbnail_id,
	MAX(task_list.project_id) AS project_id,
	MAX(task_list.project_title) AS project_title,
	MAX(task_list.project_slug) AS project_slug,
	MAX(task_list.task_list_id) AS task_list_id,
	MAX(task_list.task_list_title) AS task_list_title,
	MAX(task_list.task_list_slug) AS task_list_slug,
	MAX(CASE WHEN pm.meta_key = '_start' THEN pm.meta_value ELSE NULL END) AS start,
	MAX(CASE WHEN pm.meta_key = '_due' THEN pm.meta_value ELSE NULL END) AS due,
	MAX(CASE WHEN pm.meta_key = '_completed' THEN pm.meta_value ELSE NULL END) AS completed,
	MAX(CASE WHEN pm.meta_key = '_completed_on' THEN pm.meta_value ELSE NULL END) AS completed_on,
	MAX(CASE WHEN pm.meta_key = '_completed_by' THEN pm.meta_value ELSE NULL END) AS completed_by,
	MAX(CASE WHEN pm.meta_key = '_task_privacy' THEN pm.meta_value ELSE NULL END) AS task_privacy,
	COALESCE(
		MAX(CASE WHEN pm.meta_key = '_team' THEN pm.meta_value ELSE NULL END),
		GROUP_CONCAT(CASE WHEN pm.meta_key = '_assigned' THEN CONCAT('\"', pm.meta_value, '\":\"\"') ELSE NULL END)
	) AS team
FROM
	{posts} p
		INNER JOIN {postmeta} pm
			ON p.ID = pm.post_id
		INNER JOIN (
			SELECT DISTINCT
				p_task_list.ID AS task_list_id,
				p_task_list.post_title AS task_list_title,
				p_task_list.post_name AS task_list_slug,
				p_task_list.post_parent AS task_list_parent,
				project.id AS project_id,
				project.title AS project_title,
				project.slug AS project_slug
			FROM
				{posts} p_task_list
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
						) all_projects
						WHERE ( {is_admin} OR team LIKE {team_like} )
					) project ON p_task_list.post_parent = project.id
			WHERE p_task_list.post_type = 'cpm_task_list'
		) task_list
			ON p.post_parent = task_list.task_list_id
WHERE
	p.post_type = 'cpm_task'
GROUP BY
	p.ID
HAVING
	TRUE
";

	/**
	 * ID списка задач (или родителя — см. иерархию).
	 *
	 * @var int
	 */
	public $task_list_id = 0;

	/**
	 * Название списка задач.
	 *
	 * @var string
	 */
	public $task_list_title = '';

	/**
	 * Слаг списка задач.
	 *
	 * @var string
	 */
	public $task_list_slug = '';

	/**
	 * Дата/время старта.
	 *
	 * @var string
	 */
	public $start = '';

	/**
	 * Срок задачи.
	 *
	 * @var string
	 */
	public $due = '';

	/**
	 * Признак завершения: 1 / 0.
	 *
	 * @var int
	 */
	public $completed = 0;

	/**
	 * Дата/время завершения.
	 *
	 * @var string
	 */
	public $completed_on = '';

	/**
	 * ID пользователя, завершившего задачу.
	 *
	 * @var int
	 */
	public $completed_by = 0;

	/**
	 * Приватность: yes — только сотрудники, no — все участники.
	 *
	 * @var string
	 */
	public $task_privacy = 'no';

	/**
	 * Защита от циклов при разборе parent = задача.
	 *
	 * @var bool
	 */
	private $resolving_project = false;

	/**
	 * @param array $args Данные сущности.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		$this->task_list_id    = isset( $args['task_list_id'] ) ? (int) $args['task_list_id'] : 0;
		$this->task_list_title = isset( $args['task_list_title'] ) ? (string) $args['task_list_title'] : '';
		$this->task_list_slug  = isset( $args['task_list_slug'] ) ? (string) $args['task_list_slug'] : '';
		$this->start           = isset( $args['start'] ) ? (string) $args['start'] : '';
		$this->due             = isset( $args['due'] ) ? (string) $args['due'] : '';
		$this->completed       = isset( $args['completed'] ) ? (int) $args['completed'] : 0;
		$this->completed_on    = isset( $args['completed_on'] ) ? (string) $args['completed_on'] : '';
		$this->completed_by    = isset( $args['completed_by'] ) ? (int) $args['completed_by'] : 0;
		$this->task_privacy    = isset( $args['task_privacy'] ) ? (string) $args['task_privacy'] : 'no';
	}

	/**
	 * Проект с учётом иерархии parent: список / проект / задача.
	 *
	 * @return Project|null
	 */
	public function get_project() {
		if ( ! empty( $this->project_id ) ) {
			return parent::get_project();
		}
		if ( $this->resolving_project ) {
			return null;
		}
		$this->resolving_project = true;
		try {
			return $this->resolve_project_from_parent();
		} finally {
			$this->resolving_project = false;
		}
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
						'_start'        => $this->start,
						'_due'          => $this->due,
						'_completed'    => $this->completed,
						'_completed_on' => $this->completed_on,
						'_completed_by' => $this->completed_by,
						'_task_privacy' => $this->task_privacy,
					)
				),
			)
		);
	}

	/**
	 * Вычисляет project_id по типу записи parent (единичная задача).
	 *
	 * @return Project|null
	 */
	private function resolve_project_from_parent() {
		if ( empty( $this->parent ) ) {
			return null;
		}

		$parent_type = get_post_type( $this->parent );
		$manager     = Core_Manager::get_instance();

		if ( Project::CPT === $parent_type ) {
			$this->project_id = (int) $this->parent;
			return parent::get_project();
		}

		if ( Task_List::CPT === $parent_type ) {
			$lists = $manager->load_list( 'task_list', array( 'id' => $this->parent ) );
			if ( empty( $lists ) ) {
				return null;
			}
			$list                  = $lists[0];
			$this->task_list_id    = (int) $list->id;
			$this->task_list_title = isset( $list->title ) ? (string) $list->title : '';
			$this->task_list_slug  = isset( $list->slug ) ? (string) $list->slug : '';
			$this->project_id      = (int) $list->project_id;
			return $list->get_project();
		}

		if ( self::CPT === $parent_type ) {
			$tasks = $manager->load_list( 'task', array( 'id' => $this->parent ) );
			if ( empty( $tasks ) ) {
				return null;
			}
			$project = $tasks[0]->get_project();
			if ( $project ) {
				$this->project_id = (int) $project->id;
			}
			return $project;
		}

		return null;
	}
}
