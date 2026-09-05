<?php
/**
 * Class Activity_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

/**
 * REST-контроллер журнала действий (только чтение).
 *
 * Спецификация: docs/rest-api/маршруты.md, docs/rest-api/схемы.md
 */
class Activity_Controller extends Entity_Controller {

	/**
	 * Тип сущности ядра.
	 *
	 * @return string
	 */
	protected function get_type() {
		return 'activity';
	}

	/**
	 * Активность доступна только для чтения.
	 *
	 * @return bool
	 */
	protected function supports_write() {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_fields() {
		return array(
			'id'         => array( 'type' => 'integer', 'read' => true ),
			'parent'     => array( 'type' => 'integer', 'read' => true ),
			'author'     => array( 'type' => 'integer', 'read' => true ),
			'content'    => array( 'type' => 'string', 'read' => true ),
			'created_at' => array( 'type' => 'string', 'read' => true ),
		);
	}
}
