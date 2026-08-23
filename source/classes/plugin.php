<?php
/**
 * Class Plugin.
 *
 * @package CPM
 */

namespace CPM\v3;

/**
 * Инициализирует плагин и его модули.
 *
 * Спецификация: docs/02-plugin.md
 */
class Plugin {

	/**
	 * Путь к папке плагина.
	 *
	 * @var string
	 */
	public $CPM_PLUGIN_DIR;

	/**
	 * URL к папке плагина для служебных целей.
	 *
	 * @var string
	 */
	public $CPM_PLUGIN_URL;

	/**
	 * Экземпляр класса Settings.
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Экземпляр менеджера ядра.
	 *
	 * @var \CPM\v3\Core\Core_Manager
	 */
	public $core;

	/**
	 * Экземпляр менеджера REST API.
	 *
	 * @var REST_API_Manager
	 */
	public $rest_api;

	/**
	 * Экземпляр менеджера UI.
	 *
	 * @var UI_Manager
	 */
	public $ui;

	/**
	 * Экземпляр менеджера расширений.
	 *
	 * @var Extensions_Manager
	 */
	public $extensions;

	/**
	 * Экземпляр класса Plugin (синглтон).
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Конструктор.
	 *
	 * @param string $plugin_folder Путь к папке плагина.
	 * @param string $plugin_url    URL к папке плагина.
	 */
	public function __construct( $plugin_folder, $plugin_url ) {
		self::$instance = $this;

		$this->CPM_PLUGIN_DIR = $plugin_folder;
		$this->CPM_PLUGIN_URL = $plugin_url;

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Возвращает экземпляр Plugin.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Создаёт экземпляры менеджеров модулей по хуку init.
	 */
	public function init() {
		$this->core       = new \CPM\v3\Core\Core_Manager();
		$this->rest_api   = new REST_API_Manager();
		$this->ui         = new UI_Manager();
		$this->extensions = new Extensions_Manager();
		$this->settings   = new Settings();
	}

	/**
	 * Централизованное логирование.
	 *
	 * Уровни: error, warn, info, debug. Уровень debug должен быть удалён
	 * из кода перед релизом (см. docs/02-plugin.md).
	 *
	 * @param string $message Сообщение.
	 * @param string $level   Уровень сообщения.
	 */
	public function log( $message, $level = 'info' ) {
		if ( ! WP_DEBUG ) {
			return;
		}

		error_log( '[' . date( 'd.m.Y H:i:s' ) . '] CPM: ' . $level . ': ' . $message );
	}
}
