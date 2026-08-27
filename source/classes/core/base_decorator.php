<?php
/**
 * Class Base_Decorator.
 *
 * @package CPM
 */

namespace CPM\v3\Core;

/**
 * Базовый декоратор прав доступа к сущности.
 *
 * Спецификация: docs/core/base-decorator.md
 */
abstract class Base_Decorator implements IEntity {

	/**
	 * Обёрнутая сущность.
	 *
	 * @var IEntity
	 */
	protected $entity;

	/**
	 * @param IEntity $entity Обёрнутая сущность.
	 * @param array   $args   Аргументы фабрики Core_Manager (зарезервировано).
	 */
	public function __construct( $entity, $args = array() ) {
		$this->entity = $entity;
	}

	/**
	 * Разрешена ли модификация текущему пользователю.
	 *
	 * @return bool
	 */
	abstract protected function can_modify();

	/**
	 * Разрешено ли чтение. По умолчанию да; Stuff_Decorator ограничивает.
	 *
	 * @return bool
	 */
	protected function can_read() {
		return true;
	}

	/**
	 * @return string
	 */
	public function get_type() {
		$this->assert_can_read();
		return $this->entity->get_type();
	}

	/**
	 * @return Project|null
	 */
	public function get_project() {
		$this->assert_can_read();
		return $this->entity->get_project();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws AccessDeniedException Модификация запрещена.
	 */
	public function save() {
		$this->assert_can_modify();
		$this->entity->save();
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param bool $is_recursion Вложенный шаг рекурсии.
	 * @throws AccessDeniedException Модификация запрещена.
	 */
	public function delete( $is_recursion = false ) {
		$this->assert_can_modify();
		$this->entity->delete( $is_recursion );
	}

	/**
	 * @param string $name Имя свойства.
	 * @return mixed
	 */
	public function __get( $name ) {
		$this->assert_can_read();
		return $this->entity->$name;
	}

	/**
	 * @param string $name  Имя свойства.
	 * @param mixed  $value Значение.
	 * @throws AccessDeniedException Модификация запрещена.
	 */
	public function __set( $name, $value ) {
		$this->assert_can_modify();
		$this->entity->$name = $value;
	}

	/**
	 * @param string $name Имя свойства.
	 * @return bool
	 */
	public function __isset( $name ) {
		return isset( $this->entity->$name );
	}

	/**
	 * @param string $name Имя метода.
	 * @param array  $args Аргументы.
	 * @return mixed
	 * @throws AccessDeniedException Нет прав на чтение или модификацию.
	 */
	public function __call( $name, $args ) {
		$is_getter = ( 0 === strpos( $name, 'get_' ) || 0 === strpos( $name, 'is_' ) );
		if ( $is_getter ) {
			$this->assert_can_read();
		} else {
			$this->assert_can_modify();
		}
		return call_user_func_array( array( $this->entity, $name ), $args );
	}

	/**
	 * @throws AccessDeniedException
	 */
	protected function assert_can_modify() {
		if ( ! $this->can_modify() ) {
			throw new AccessDeniedException( 'Modification is not allowed' );
		}
	}

	/**
	 * @throws AccessDeniedException
	 */
	protected function assert_can_read() {
		if ( ! $this->can_read() ) {
			throw new AccessDeniedException( 'Read is not allowed' );
		}
	}
}
