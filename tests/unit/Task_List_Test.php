<?php
/**
 * Юнит-тесты Task_List.
 *
 * Матрица tests/покрытие.md: get_wp_args() (мета _milestone); свойство milestone.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Task_List;

class Task_List_Test extends Cpm_TestCase {

	public function test_cpt_default_milestone_and_get_type() {
		$this->assertSame( 'cpm_task_list', Task_List::CPT );
		$list = new Task_List();
		$this->assertSame( 'task_list', $list->get_type() );
		$this->assertSame( -1, $list->milestone );
	}

	public function test_get_wp_args_adds_milestone_meta() {
		$list = new Task_List(
			array(
				'id'         => 5,
				'project_id' => 10,
				'milestone'  => 8,
			)
		);

		$args = $this->invoke_protected( $list, 'get_wp_args' );

		$this->assertSame( Task_List::CPT, $args['post_type'] );
		$this->assertSame( 8, $args['meta_input']['_milestone'] );
		$this->assertArrayHasKey( '_team', $args['meta_input'] );
	}

	public function test_sql_joins_project_and_selects_milestone() {
		$sql = Task_List::$SQL;
		$this->assertStringContainsString( 'cpm_task_list', $sql );
		$this->assertStringContainsString( '_milestone', $sql );
		$this->assertStringContainsString( 'project_id', $sql );
		$this->assertStringContainsString( '{is_admin}', $sql );
	}
}
