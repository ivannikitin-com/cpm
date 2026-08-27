<?php
/**
 * Class ReadOnly_Decorator.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Запрещает любые модификации сущности.
 *
 * Спецификация: docs/core/read-only-decorator.md
 */
class ReadOnly_Decorator extends Base_Decorator {

	/**
	 * {@inheritdoc}
	 */
	protected function can_modify() {
		return false;
	}
}
