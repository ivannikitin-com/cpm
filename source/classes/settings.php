<?php
/**
 * Class Settings.
 *
 * @package CPM
 */

namespace CPM\v3;

/**
 * Хранение, доступ и интерфейс работы с настройками CPM.
 *
 * Спецификация: docs/04-settings.md
 */
class Settings {

	const OPTION_NAME = 'cpm_settings';

	/**
	 * Значения настроек.
	 *
	 * @var array
	 */
	protected $values;

	/**
	 * Конструктор.
	 */
	public function __construct() {
		$this->values = wp_parse_args( get_option( self::OPTION_NAME, array() ), $this->get_defaults() );
	}

	/**
	 * Возвращает значение настройки.
	 *
	 * @param string $key     Ключ настройки.
	 * @param mixed  $default Значение по умолчанию.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		if ( array_key_exists( $key, $this->values ) ) {
			return $this->values[ $key ];
		}
		return $default;
	}

	/**
	 * Сохраняет значение настройки.
	 *
	 * @param string $key   Ключ настройки.
	 * @param mixed  $value Значение.
	 */
	public function set( $key, $value ) {
		$this->values[ $key ] = $value;
		update_option( self::OPTION_NAME, $this->values );
	}

	/**
	 * Значения настроек по умолчанию.
	 *
	 * @return array
	 */
	protected function get_defaults() {
		return array(
			'backward_compatibility' => CPM_BACKWARD_COMPATIBILITY,
		);
	}
}
