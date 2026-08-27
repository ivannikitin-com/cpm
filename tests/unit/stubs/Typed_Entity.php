<?php
/**
 * Сущность с задаваемым get_type() для тестов таблицы прав.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

class Typed_Entity extends Demo_Entity {

	/**
	 * Подмена get_type().
	 *
	 * @var string
	 */
	public $type_name = 'project';

	/**
	 * Проект, который вернёт get_project().
	 *
	 * @var Project|null
	 */
	public $project_ref;

	/**
	 * @return string
	 */
	public function get_type() {
		return $this->type_name;
	}

	/**
	 * @return Project|null
	 */
	public function get_project() {
		if ( isset( $this->project_ref ) ) {
			return $this->project_ref;
		}
		return parent::get_project();
	}
}
