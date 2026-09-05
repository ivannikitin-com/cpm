<?php
/**
 * Class Task_List_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

/**
 * REST-контроллер списка задач.
 *
 * Спецификация: docs/rest-api/маршруты.md, docs/rest-api/схемы.md
 */
class Task_List_Controller extends Entity_Controller {

	/**
	 * Тип сущности ядра.
	 *
	 * @return string
	 */
	protected function get_type() {
		return 'task_list';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_fields() {
		$fields = $this->entity_fields();
		$fields['parent']['required'] = true; // Список задач создаётся в проекте.

		return array_merge(
			$fields,
			$this->project_entity_fields(),
			array(
				'milestone' => array( 'type' => 'integer', 'read' => true, 'create' => true, 'update' => true ),
			)
		);
	}
}
