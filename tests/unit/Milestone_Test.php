<?php
/**
 * Юнит-тесты Milestone.
 *
 * Матрица tests/покрытие.md: get_wp_args() (мета _due/_completed).
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Milestone;

class Milestone_Test extends Cpm_TestCase {

	public function test_cpt_and_defaults() {
		$this->assertSame( 'cpm_milestone', Milestone::CPT );
		$milestone = new Milestone();
		$this->assertSame( 'milestone', $milestone->get_type() );
		$this->assertSame( '', $milestone->due );
		$this->assertSame( 0, $milestone->completed );
	}

	public function test_get_wp_args_adds_due_and_completed_meta() {
		$milestone = new Milestone(
			array(
				'id'         => 4,
				'project_id' => 10,
				'due'        => '2016-11-14 12:00:00',
				'completed'  => 1,
			)
		);

		$args = $this->invoke_protected( $milestone, 'get_wp_args' );

		$this->assertSame( Milestone::CPT, $args['post_type'] );
		$this->assertSame( '2016-11-14 12:00:00', $args['meta_input']['_due'] );
		$this->assertSame( 1, $args['meta_input']['_completed'] );
	}

	public function test_sql_selects_due_and_completed() {
		$sql = Milestone::$SQL;
		$this->assertStringContainsString( 'cpm_milestone', $sql );
		$this->assertStringContainsString( '_due', $sql );
		$this->assertStringContainsString( '_completed', $sql );
	}
}
