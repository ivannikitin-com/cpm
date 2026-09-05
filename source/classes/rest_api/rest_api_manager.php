<?php
/**
 * Class REST_API_Manager.
 *
 * @package CPM
 */

namespace CPM\v3\REST;

use CPM\v3\Base_Manager;

/**
 * Менеджер модуля REST API.
 *
 * Спецификация: docs/rest-api/README.md, docs/rest-api/архитектура.md
 */
class REST_API_Manager extends Base_Manager {

	/**
	 * Конструктор.
	 */
	public function __construct() {
		parent::__construct();
		$this->register();
	}

	/**
	 * Регистрирует обработчик маршрутов на rest_api_init.
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Регистрирует маршруты модуля.
	 */
	public function register_routes() {
		// Контроллеры подключаются и регистрируются здесь (см. docs/rest-api/архитектура.md).
	}
}
