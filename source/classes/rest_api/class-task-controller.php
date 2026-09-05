<?php
/**
 * Class Task_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

/**
 * REST-контроллер задачи.
 *
 * Спецификация: docs/rest-api/маршруты.md, docs/rest-api/схемы.md
 */
class Task_Controller extends Entity_Controller {

	/**
	 * Тип сущности ядра.
	 *
	 * @return string
	 */
	protected function get_type() {
		return 'task';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_fields() {
		$fields = $this->entity_fields();
		$fields['parent']['required'] = true; // Задача создаётся в списке задач (или напрямую в проекте).

		return array_merge(
			$fields,
			$this->project_entity_fields(),
			array(
				'task_list_id'    => array( 'type' => 'integer', 'read' => true ),
				'task_list_title' => array( 'type' => 'string', 'read' => true ),
				'task_list_slug'  => array( 'type' => 'string', 'read' => true ),
				'start'           => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
				'due'             => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
				'completed'       => array( 'type' => 'integer', 'read' => true, 'create' => true, 'update' => true ),
				'completed_on'    => array( 'type' => 'string', 'read' => true ),
				'completed_by'    => array( 'type' => 'integer', 'read' => true ),
				'task_privacy'    => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
			)
		);
	}
}
