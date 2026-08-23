<?php
/**
 * Class UI_Manager.
 *
 * @package CPM
 */

namespace CPM\v3\UI;

use CPM\v3\Base_Manager;

/**
 * Менеджер модуля пользовательского интерфейса.
 *
 * Классы UI загружаются только при рендеринге интерфейса или шорткодов CPM.
 * Спецификация: docs/07-модуль-ui.md
 */
class UI_Manager extends Base_Manager {

	/**
	 * Конструктор.
	 */
	public function __construct() {
		parent::__construct();
	}
}
