<?php
/**
 * Class Milestone_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

/**
 * REST-контроллер вехи.
 *
 * Спецификация: docs/rest-api/маршруты.md, docs/rest-api/схемы.md
 */
class Milestone_Controller extends Entity_Controller {

	/**
	 * Тип сущности ядра.
	 *
	 * @return string
	 */
	protected function get_type() {
		return 'milestone';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_fields() {
		$fields = $this->entity_fields();
		$fields['parent']['required'] = true; // Веха создаётся в проекте.

		return array_merge(
			$fields,
			$this->project_entity_fields(),
			array(
				'due'       => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
				'completed' => array( 'type' => 'integer', 'read' => true, 'create' => true, 'update' => true ),
			)
		);
	}
}
