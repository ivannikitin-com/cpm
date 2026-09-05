<?php
/**
 * Class Note_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

/**
 * REST-контроллер заметки (cpm_docs).
 *
 * Спецификация: docs/rest-api/маршруты.md, docs/rest-api/схемы.md
 */
class Note_Controller extends Entity_Controller {

	/**
	 * Тип сущности ядра.
	 *
	 * @return string
	 */
	protected function get_type() {
		return 'note';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_fields() {
		$fields = $this->entity_fields();
		unset( $fields['team'] ); // У заметки нет собственной команды.
		$fields['parent']['required'] = true; // Заметка создаётся в проекте.

		return array_merge(
			$fields,
			$this->project_entity_fields(),
			array(
				'doc_type' => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true ),
			)
		);
	}
}
