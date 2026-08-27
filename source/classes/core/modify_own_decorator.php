<?php
/**
 * Class ModifyOwn_Decorator.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Разрешает модификацию только своих сущностей (автор = текущий пользователь).
 *
 * Спецификация: docs/core/modify-own-decorator.md
 */
class ModifyOwn_Decorator extends Base_Decorator {

	/**
	 * {@inheritdoc}
	 */
	protected function can_modify() {
		return (int) $this->entity->author === (int) get_current_user_id();
	}
}
