<?php
/**
 * Сквозные юнит-тесты нормативной таблицы прав.
 *
 * Матрица tests/покрытие.md: для каждой пары «роль × тип» decorate()
 * возвращает ожидаемый декоратор (docs/core/права-и-роли.md).
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\ACL;
use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\Modify_Decorator;
use CPM\v3\Core\ModifyOwn_Decorator;
use CPM\v3\Core\Project;
use CPM\v3\Core\ReadOnly_Decorator;
use CPM\v3\Core\Stuff_Decorator;
use CPM\v3\Core\Typed_Entity;
use CPM\v3\Plugin;

class rights_table_Test extends Cpm_TestCase {

	/**
	 * @dataProvider provider_role_type_decorator
	 * @param string      $type               Тип сущности.
	 * @param string      $role               Роль ACL.
	 * @param string|null $expected_decorator FQCN декоратора или null (no_access).
	 */
	public function test_decorate_matches_rights_table( $type, $role, $expected_decorator ) {
		$core   = $this->make_core();
		$entity = $this->entity_with_role( $type, $role );

		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 12 );
		\WP_Mock::userFunction( 'user_can' )->andReturn( false );

		$wrapped = $core->decorate( $entity );

		if ( null === $expected_decorator ) {
			$this->assertNull( $wrapped );
			return;
		}

		$this->assertInstanceOf( $expected_decorator, $wrapped );
	}

	/**
	 * @dataProvider provider_entity_types
	 * @param string $type Тип сущности.
	 */
	public function test_administrator_always_gets_modify_decorator( $type ) {
		$core   = $this->make_core();
		$entity = $this->getMockBuilder( Typed_Entity::class )
			->onlyMethods( array( 'get_project' ) )
			->getMock();
		$entity->type_name = $type;
		$entity->expects( $this->never() )->method( 'get_project' );

		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 1 );
		\WP_Mock::userFunction( 'user_can' )->once()->with( 1, 'manage_options' )->andReturn( true );

		$wrapped = $core->decorate( $entity );
		$this->assertInstanceOf( Modify_Decorator::class, $wrapped );
	}

	/**
	 * @dataProvider provider_entity_types
	 * @param string $type Тип сущности.
	 */
	public function test_no_role_always_gets_readonly_decorator( $type ) {
		$core          = $this->make_core();
		$entity        = new Typed_Entity();
		$entity->type_name   = $type;
		$entity->project_ref = new Project( array( 'id' => 1, 'team' => '"99":manager' ) );

		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 12 );
		\WP_Mock::userFunction( 'user_can' )->andReturn( false );

		$wrapped = $core->decorate( $entity );
		$this->assertInstanceOf( ReadOnly_Decorator::class, $wrapped );
	}

	/**
	 * Нормативная таблица: роль × тип → декоратор.
	 *
	 * @return array
	 */
	public function provider_role_type_decorator() {
		return array(
			'project manager'      => array( 'project', ACL::MANAGER, Modify_Decorator::class ),
			'project co_worker'    => array( 'project', ACL::CO_WORKER, ReadOnly_Decorator::class ),
			'project client'       => array( 'project', ACL::CLIENT, ReadOnly_Decorator::class ),
			'task_list manager'    => array( 'task_list', ACL::MANAGER, ReadOnly_Decorator::class ),
			'task_list co_worker'  => array( 'task_list', ACL::CO_WORKER, ReadOnly_Decorator::class ),
			'task_list client'     => array( 'task_list', ACL::CLIENT, ReadOnly_Decorator::class ),
			'task manager'         => array( 'task', ACL::MANAGER, Modify_Decorator::class ),
			'task co_worker'       => array( 'task', ACL::CO_WORKER, ModifyOwn_Decorator::class ),
			'task client'          => array( 'task', ACL::CLIENT, ModifyOwn_Decorator::class ),
			'comment manager'      => array( 'comment', ACL::MANAGER, ModifyOwn_Decorator::class ),
			'comment co_worker'    => array( 'comment', ACL::CO_WORKER, ModifyOwn_Decorator::class ),
			'comment client'       => array( 'comment', ACL::CLIENT, ModifyOwn_Decorator::class ),
			'message manager'      => array( 'message', ACL::MANAGER, ModifyOwn_Decorator::class ),
			'message co_worker'    => array( 'message', ACL::CO_WORKER, ModifyOwn_Decorator::class ),
			'message client'       => array( 'message', ACL::CLIENT, ModifyOwn_Decorator::class ),
			'milestone manager'    => array( 'milestone', ACL::MANAGER, Stuff_Decorator::class ),
			'milestone co_worker'  => array( 'milestone', ACL::CO_WORKER, Stuff_Decorator::class ),
			'milestone client'     => array( 'milestone', ACL::CLIENT, null ),
			'attachment manager'   => array( 'attachment', ACL::MANAGER, Stuff_Decorator::class ),
			'attachment co_worker' => array( 'attachment', ACL::CO_WORKER, Stuff_Decorator::class ),
			'attachment client'    => array( 'attachment', ACL::CLIENT, null ),
			'activity manager'     => array( 'activity', ACL::MANAGER, ReadOnly_Decorator::class ),
			'activity co_worker'   => array( 'activity', ACL::CO_WORKER, ReadOnly_Decorator::class ),
			'activity client'      => array( 'activity', ACL::CLIENT, null ),
			'note manager'         => array( 'note', ACL::MANAGER, ReadOnly_Decorator::class ),
			'note co_worker'       => array( 'note', ACL::CO_WORKER, ReadOnly_Decorator::class ),
			'note client'          => array( 'note', ACL::CLIENT, ReadOnly_Decorator::class ),
		);
	}

	/**
	 * Типы из нормативной таблицы плюс note.
	 *
	 * @return array
	 */
	public function provider_entity_types() {
		return array(
			'project'    => array( 'project' ),
			'task_list'  => array( 'task_list' ),
			'task'       => array( 'task' ),
			'comment'    => array( 'comment' ),
			'message'    => array( 'message' ),
			'milestone'  => array( 'milestone' ),
			'attachment' => array( 'attachment' ),
			'activity'   => array( 'activity' ),
			'note'       => array( 'note' ),
		);
	}

	/**
	 * @return Core_Manager
	 */
	private function make_core() {
		new Plugin( $this->plugin_dir(), 'http://cpm.test/' );
		return new Core_Manager();
	}

	/**
	 * Сущность с get_type() и проектом, где пользователь 12 имеет $role.
	 *
	 * @param string $type Тип.
	 * @param string $role Роль.
	 * @return Typed_Entity
	 */
	private function entity_with_role( $type, $role ) {
		$entity              = new Typed_Entity();
		$entity->type_name   = $type;
		$entity->project_ref = new Project(
			array(
				'id'   => 1,
				'team' => '"12":' . $role,
			)
		);
		return $entity;
	}
}
