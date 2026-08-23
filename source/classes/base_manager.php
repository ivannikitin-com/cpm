<?php
/**
 * Class Base_Manager.
 *
 * @package CPM
 */

namespace CPM\v3;

/**
 * Базовый класс менеджеров модулей.
 *
 * Спецификация: docs/03-base-manager.md
 */
class Base_Manager {

	/**
	 * Путь к папке плагина.
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * URL к папке плагина.
	 *
	 * @var string
	 */
	protected $plugin_url;

	/**
	 * Конструктор.
	 */
	public function __construct() {
		$this->plugin_dir = Plugin::get_instance()->CPM_PLUGIN_DIR;
		$this->plugin_url = Plugin::get_instance()->CPM_PLUGIN_URL;
	}

	/**
	 * Подключает файл класса модуля через require_once.
	 *
	 * Используется вместо автозагрузчика: классы модуля грузятся только
	 * тогда, когда они нужны (см. docs/03-base-manager.md).
	 *
	 * @param string $relative_path Относительный путь от корня плагина.
	 */
	protected function require_class( $relative_path ) {
		$file = $this->plugin_dir . $relative_path;
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
