<?php
/**
 * Юнит-тесты Note.
 *
 * Матрица tests/покрытие.md: get_wp_args() (мета _doc_type). CPT cpm_docs.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Note;

class Note_Test extends Cpm_TestCase {

	public function test_cpt_is_cpm_docs() {
		$this->assertSame( 'cpm_docs', Note::CPT );
		$note = new Note();
		$this->assertSame( 'note', $note->get_type() );
		$this->assertSame( '_custom_doc', $note->doc_type );
	}

	public function test_get_wp_args_adds_doc_type_meta() {
		$note = new Note(
			array(
				'id'         => 6,
				'parent'     => 10,
				'project_id' => 10,
				'doc_type'   => '_google_doc',
			)
		);

		$args = $this->invoke_protected( $note, 'get_wp_args' );

		$this->assertSame( Note::CPT, $args['post_type'] );
		$this->assertSame( '_google_doc', $args['meta_input']['_doc_type'] );
		$this->assertSame( 10, $args['meta_input']['_project_uploaded'] );
	}

	public function test_sql_selects_doc_type() {
		$sql = Note::$SQL;
		$this->assertStringContainsString( 'cpm_docs', $sql );
		$this->assertStringContainsString( '_doc_type', $sql );
	}
}
