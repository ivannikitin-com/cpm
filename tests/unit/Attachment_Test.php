<?php
/**
 * Юнит-тесты Attachment.
 *
 * Матрица tests/покрытие.md: get_project() через _project; методы URL/иконок.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Attachment;
use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\Project;

class Attachment_Test extends Cpm_TestCase {

	public function test_cpt_is_wordpress_attachment() {
		$this->assertSame( 'attachment', Attachment::CPT );
		$file = new Attachment();
		$this->assertSame( 'attachment', $file->get_type() );
	}

	public function test_get_project_uses_project_meta() {
		$project = new Project( array( 'id' => 15 ) );
		$core    = $this->createMock( Core_Manager::class );
		$core->expects( $this->once() )
			->method( 'load_list' )
			->with( 'project', array( 'id' => 15 ) )
			->willReturn( array( $project ) );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'wp_cache_get' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' );

		$file = new Attachment( array( 'id' => 99, 'project_id' => 15 ) );
		$this->assertSame( $project, $file->get_project() );
	}

	public function test_get_wp_args_writes_project_and_parent_meta() {
		$file = new Attachment(
			array(
				'id'         => 99,
				'parent'     => 20,
				'project_id' => 15,
				'mime_type'  => 'application/pdf',
				'title'      => 'spec',
			)
		);

		$args = $this->invoke_protected( $file, 'get_wp_args' );

		$this->assertSame( 'attachment', $args['post_type'] );
		$this->assertSame( 'inherit', $args['post_status'] );
		$this->assertSame( 'application/pdf', $args['post_mime_type'] );
		$this->assertSame( 15, $args['meta_input']['_project'] );
		$this->assertSame( 20, $args['meta_input']['_parent'] );
	}

	public function test_url_methods_go_through_rest_proxy() {
		\WP_Mock::userFunction( 'rest_url' )
			->andReturnUsing(
				function ( $path ) {
					return 'http://cpm.test/wp-json/' . $path;
				}
			);
		\WP_Mock::userFunction( 'add_query_arg' )
			->andReturnUsing(
				function ( $key, $value, $url ) {
					return $url . '?' . $key . '=' . $value;
				}
			);

		$image = new Attachment(
			array(
				'id'        => 5,
				'mime_type' => 'image/png',
			)
		);

		$this->assertSame( 'http://cpm.test/wp-json/cpm/v1/attachment/5', $image->get_url() );
		$this->assertSame( 'http://cpm.test/wp-json/cpm/v1/attachment/5?size=medium', $image->get_url( 'medium' ) );
		$this->assertSame( 'http://cpm.test/wp-json/cpm/v1/attachment/5?size=thumbnail', $image->get_thumbnail_url() );
		$this->assertSame( 'http://cpm.test/wp-json/cpm/v1/attachment/5?download=1', $image->get_download_url() );
		$this->assertTrue( $image->is_image );

		$pdf = new Attachment( array( 'id' => 6, 'mime_type' => 'application/pdf' ) );
		$this->assertSame( '', $pdf->get_thumbnail_url() );
		$this->assertFalse( $pdf->is_image );
	}

	public function test_get_icon_uses_wp_mime_type_icon() {
		\WP_Mock::userFunction( 'wp_mime_type_icon' )
			->once()
			->with( 'application/pdf' )
			->andReturn( 'http://cpm.test/pdf.png' );

		$file = new Attachment( array( 'id' => 6, 'mime_type' => 'application/pdf' ) );
		$this->assertSame( 'http://cpm.test/pdf.png', $file->get_icon() );
	}

	public function test_metadata_fills_dimensions_and_size() {
		$file = new Attachment(
			array(
				'mime_type'            => 'image/jpeg',
				'attachment_metadata'  => array(
					'width'    => 800,
					'height'   => 600,
					'filesize' => 12345,
				),
			)
		);
		$this->assertSame( 800, $file->width );
		$this->assertSame( 600, $file->height );
		$this->assertSame( 12345, $file->size );
	}

	public function test_delete_entity_deletes_attachment() {
		\WP_Mock::userFunction( 'wp_delete_attachment' )->once()->with( 99, true );
		$file = new Attachment( array( 'id' => 99 ) );
		$this->invoke_protected( $file, 'delete_entity' );
		$this->assertConditionsMet();
	}

	public function test_sql_filters_by_project_meta() {
		$sql = Attachment::$SQL;
		$this->assertStringContainsString( "post_type = 'attachment'", $sql );
		$this->assertStringContainsString( "_project", $sql );
		$this->assertStringContainsString( '{is_admin}', $sql );
	}
}
