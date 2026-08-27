<?php
/**
 * Class Project_Entity.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Абстрактная сущность, которая всегда принадлежит проекту.
 *
 * Спецификация: docs/core/entity.md
 */
abstract class Project_Entity extends Entity {

	/**
	 * ID проекта, которому принадлежит сущность.
	 *
	 * @var int
	 */
	public $project_id = 0;

	/**
	 * Название проекта (чтобы не загружать проект ради title).
	 *
	 * @var string
	 */
	public $project_title = '';

	/**
	 * Слаг проекта (для URL без доп. обращения к БД).
	 *
	 * @var string
	 */
	public $project_slug = '';

	/**
	 * @param array $args Данные сущности, включая project_id / project_title / project_slug.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		$this->project_id    = isset( $args['project_id'] ) ? (int) $args['project_id'] : 0;
		$this->project_title = isset( $args['project_title'] ) ? (string) $args['project_title'] : '';
		$this->project_slug  = isset( $args['project_slug'] ) ? (string) $args['project_slug'] : '';
	}

	/**
	 * Возвращает проект по project_id (из кэша или через Core_Manager::load_list()).
	 *
	 * @return Project|null
	 */
	public function get_project() {
		if ( empty( $this->project_id ) ) {
			return null;
		}

		$cache_key = 'cpm_project_' . $this->project_id;
		$cached    = wp_cache_get( $cache_key );
		if ( false !== $cached && null !== $cached ) {
			return $cached;
		}

		$list = Core_Manager::get_instance()->load_list(
			'project',
			array( 'id' => $this->project_id )
		);

		$project = ! empty( $list ) ? $list[0] : null;
		if ( $project ) {
			wp_cache_set( $cache_key, $project );
		}

		return $project;
	}

	/**
	 * Цепочка картинки для сущностей проекта заканчивается самим проектом.
	 *
	 * @return Entity|null
	 */
	protected function get_parent_entity() {
		if ( empty( $this->project_id ) ) {
			return null;
		}
		$project = $this->get_project();
		if ( $project && (int) $project->id === (int) $this->id ) {
			return null;
		}
		return $project;
	}
}
