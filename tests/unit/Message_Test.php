<?php
/**
 * Юнит-тесты Message.
 *
 * Матрица tests/покрытие.md: get_wp_args() (мета _message_privacy/_milestone).
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Message;

class Message_Test extends Cpm_TestCase {

	public function test_cpt_and_defaults() {
		$this->assertSame( 'cpm_message', Message::CPT );
		$message = new Message();
		$this->assertSame( 'message', $message->get_type() );
		$this->assertSame( 'no', $message->message_privacy );
		$this->assertSame( 0, $message->milestone );
	}

	public function test_get_wp_args_adds_privacy_and_milestone_meta() {
		$message = new Message(
			array(
				'id'               => 9,
				'project_id'       => 10,
				'message_privacy'  => 'yes',
				'milestone'        => 4,
			)
		);

		$args = $this->invoke_protected( $message, 'get_wp_args' );

		$this->assertSame( Message::CPT, $args['post_type'] );
		$this->assertSame( 'yes', $args['meta_input']['_message_privacy'] );
		$this->assertSame( 4, $args['meta_input']['_milestone'] );
		$this->assertArrayNotHasKey( '_files', $args['meta_input'] );
	}

	public function test_sql_selects_privacy_and_milestone() {
		$sql = Message::$SQL;
		$this->assertStringContainsString( 'cpm_message', $sql );
		$this->assertStringContainsString( '_message_privacy', $sql );
		$this->assertStringContainsString( '_milestone', $sql );
	}
}
