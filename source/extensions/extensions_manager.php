<?php
/**
 * Class Extensions_Manager.
 *
 * @package CPM
 */

namespace CPM\v3\Extensions;

use CPM\v3\Base_Manager;

/**
 * Менеджер расширений системы.
 *
 * Единый механизм загрузки и управления расширениями.
 * Спецификация: docs/08-модуль-расширения.md
 */
class Extensions_Manager extends Base_Manager {

	/**
	 * Конструктор.
	 */
	public function __construct() {
		parent::__construct();
	}
}
