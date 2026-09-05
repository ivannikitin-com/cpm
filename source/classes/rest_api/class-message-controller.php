<?php
/**
 * Class Message_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

/**
 * REST-контроллер обсуждения.
 *
 * Спецификация: docs/rest-api/маршруты.md, docs/rest-api/схемы.md
 */
class Message_Controller extends Entity_Controller {

	/**
	 * Тип сущности ядра.
	 *
	 * @return string
	 */
	protected function get_type() {
		return 'message';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_fields() {
		$fields = $this->entity_fields();
		$fields['parent']['required'] = true; // Обсуждение создаётся в проекте.

		return array_merge(
			$fields,
			$this->project_entity_fields(),
			array(
				'message_privacy' => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
				'milestone'       => array( 'type' => 'integer', 'read' => true, 'create' => true, 'update' => true ),
				'files'           => array( 'type' => 'object', 'read' => true ),
			)
		);
	}
}
