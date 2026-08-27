<?php
/**
 * Конкретный наследник Entity для юнит-тестов.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

class Demo_Entity extends Entity {

	/**
	 * Подмена родителя для тестов цепочки картинки.
	 *
	 * @var Entity|null
	 */
	public $parent_entity;

	/**
	 * @return Project|null
	 */
	public function get_project() {
		return null;
	}

	/**
	 * Публичная обёртка над protected get_wp_args().
	 *
	 * @return array
	 */
	public function expose_wp_args() {
		return $this->get_wp_args();
	}

	/**
	 * Публичная обёртка над protected delete_entity().
	 */
	public function expose_delete_entity() {
		$this->delete_entity();
	}

	/**
	 * @return Entity|null
	 */
	protected function get_parent_entity() {
		if ( isset( $this->parent_entity ) ) {
			return $this->parent_entity;
		}
		return parent::get_parent_entity();
	}
}
