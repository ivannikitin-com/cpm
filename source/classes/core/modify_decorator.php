<?php
/**
 * Class Modify_Decorator.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Разрешает любые модификации сущности.
 *
 * Спецификация: docs/core/modify-decorator.md
 */
class Modify_Decorator extends Base_Decorator {

	/**
	 * {@inheritdoc}
	 */
	protected function can_modify() {
		return true;
	}
}
