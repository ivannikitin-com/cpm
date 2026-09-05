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
	 * Namespace маршрутов.
	 *
	 * @var string
	 */
	const NAMESPACE = 'cpm/v1';

	/**
	 * Контроллеры сущностей: тип ядра → имя класса (без namespace).
	 *
	 * @var string[]
	 */
	private static $controllers = array(
		'project'    => 'Project_Controller',
		'task_list'  => 'Task_List_Controller',
		'task'       => 'Task_Controller',
		'message'    => 'Message_Controller',
		'milestone'  => 'Milestone_Controller',
		'note'       => 'Note_Controller',
		'comment'    => 'Comment_Controller',
		'attachment' => 'Attachment_Controller',
		'activity'   => 'Activity_Controller',
	);

	/**
	 * Маршруты уже зарегистрированы (защита от повторного rest_api_init).
	 *
	 * @var bool
	 */
	private static $routes_registered = false;

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
	 * Регистрирует маршруты модуля: подключает контроллеры и вызывает их register_routes().
	 *
	 * Выполняется по хуку rest_api_init, т.е. только при REST-запросе
	 * (экономия памяти — см. docs/03-base-manager.md).
	 */
	public function register_routes() {
		if ( self::$routes_registered ) {
			return;
		}
		self::$routes_registered = true;

		$this->require_controller_base();

		foreach ( self::$controllers as $controller ) {
			$this->require_controller( $controller );
			$class    = __NAMESPACE__ . '\\' . $controller;
			$instance = new $class();
			$instance->register_routes();
		}
	}

	/**
	 * Подключает базовые классы контроллеров.
	 */
	private function require_controller_base() {
		$this->require_class( 'classes/rest_api/class-base-controller.php' );
		$this->require_class( 'classes/rest_api/class-entity-controller.php' );
	}

	/**
	 * Подключает файл контроллера.
	 *
	 * @param string $controller Имя класса контроллера (без namespace).
	 */
	private function require_controller( $controller ) {
		$file = 'classes/rest_api/class-' . $this->class_to_file( $controller ) . '.php';
		$this->require_class( $file );
	}

	/**
	 * Имя класса → имя файла (Project_Controller → project-controller).
	 *
	 * @param string $class Имя класса.
	 * @return string
	 */
	private function class_to_file( $class ) {
		return str_replace( '_', '-', strtolower( $class ) );
	}
}
