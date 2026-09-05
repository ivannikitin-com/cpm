<?php
/**
 * Class Comment_Controller.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

/**
 * REST-контроллер комментария (таблица comments).
 *
 * Спецификация: docs/rest-api/маршруты.md, docs/rest-api/схемы.md
 */
class Comment_Controller extends Entity_Controller {

	/**
	 * Тип сущности ядра.
	 *
	 * @return string
	 */
	protected function get_type() {
		return 'comment';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function get_fields() {
		return array(
			'id'         => array( 'type' => 'integer', 'read' => true ),
			'parent'     => array( 'type' => 'integer', 'read' => true, 'create' => true, 'required' => true ),
			'author'     => array( 'type' => 'integer', 'read' => true ),
			'content'    => array( 'type' => 'string', 'read' => true, 'create' => true, 'update' => true, 'required' => true ),
			'created_at' => array( 'type' => 'string', 'read' => true ),
			'files'      => array( 'type' => 'object', 'read' => true ),
		);
	}
}
