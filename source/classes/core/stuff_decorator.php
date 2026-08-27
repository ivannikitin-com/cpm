<?php
/**
 * Class Stuff_Decorator.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Сущности только для сотрудников (manager / co_worker); клиенту доступ закрыт.
 *
 * Спецификация: docs/core/stuff-decorator.md
 */
class Stuff_Decorator extends Base_Decorator {

	/**
	 * @return bool
	 */
	protected function is_staff() {
		$role = ACL::get_role( $this->entity );
		return in_array( $role, array( ACL::MANAGER, ACL::CO_WORKER ), true );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function can_read() {
		return $this->is_staff();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function can_modify() {
		return $this->is_staff()
			&& (int) $this->entity->author === (int) get_current_user_id();
	}
}
