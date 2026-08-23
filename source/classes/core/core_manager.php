<?php
/**
 * Class Core_Manager.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

use CPM\v3\Base_Manager;

/**
 * Менеджер ядра CPM: загрузка классов ядра, регистрация CPT, фабрика сущностей.
 *
 * Спецификация: docs/core/core-manager.md
 */
class Core_Manager extends Base_Manager {

	/**
	 * Маппинг «тип сущности → класс сущности».
	 *
	 * Ключ — строковый тип из Entity::get_type(), значение — имя класса
	 * в пространстве имён \CPM\v3\Core.
	 *
	 * @var string[]
	 */
	private static $entity_types = array(
		'project'   => 'Project',
		'task_list' => 'Task_List',
		'task'      => 'Task',
		'message'   => 'Message',
		'milestone' => 'Milestone',
		'note'      => 'Note',
	);

	/**
	 * Экземпляр менеджера ядра (синглтон).
	 *
	 * @var Core_Manager
	 */
	private static $instance;

	/**
	 * Сервисный класс проверки доступа (ACL).
	 *
	 * @var string
	 */
	public $acl = '\CPM\v3\Core\ACL';

	/**
	 * Конструктор: загружает классы ядра и регистрирует CPT.
	 */
	public function __construct() {
		parent::__construct();
		$this->require_core_classes();
		$this->register_cpt();
	}

	/**
	 * Возвращает экземпляр менеджера ядра.
	 *
	 * @return Core_Manager
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Фабричный метод выборки сущностей.
	 *
	 * Возвращает массив объектов сущностей, обёрнутых декоратором.
	 * Реализация: docs/core/core-manager.md.
	 *
	 * @param string $type Тип сущности.
	 * @param array  $args Аргументы фильтрации.
	 * @return Entity[]
	 */
	public function load_list( $type, $args = array() ) {
		return array();
	}

	/**
	 * Фабричный метод создания сущности.
	 *
	 * Создаёт сущность, сохраняет в БД и возвращает объект,
	 * обёрнутый декоратором. Реализация: docs/core/core-manager.md.
	 *
	 * @param string $type Тип сущности.
	 * @param array  $args Данные сущности.
	 * @return Entity|null
	 */
	public function create( $type, $args = array() ) {
		return null;
	}

	/**
	 * Сбрасывает кэши ядра и вызывает flush() у ACL.
	 */
	public function flush() {
		if ( method_exists( $this->acl, 'flush' ) ) {
			call_user_func( array( $this->acl, 'flush' ) );
		}
	}

	/**
	 * Загружает все файлы классов ядра через require.
	 *
	 * Список пополняется по мере реализации классов (docs/core/README.md).
	 */
	private function require_core_classes() {
		$files = array(
			'acl.php',
		);

		foreach ( $files as $file ) {
			$this->require_class( 'classes/core/' . $file );
		}
	}

	/**
	 * Регистрирует Custom Post Types ядра.
	 *
	 * Вызывается статический метод register() каждого класса сущности.
	 */
	private function register_cpt() {
		foreach ( self::$entity_types as $class ) {
			$class = __NAMESPACE__ . '\\' . $class;
			if ( method_exists( $class, 'register' ) ) {
				call_user_func( array( $class, 'register' ) );
			}
		}
	}
}
