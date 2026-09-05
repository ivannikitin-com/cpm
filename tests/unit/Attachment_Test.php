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

	public function test_allowed_mime_types_have_pdf_and_images() {
		$mimes = Attachment::get_allowed_mime_types();
		$this->assertArrayHasKey( 'pdf', $mimes );
		$this->assertSame( 'application/pdf', $mimes['pdf'] );
		$this->assertArrayHasKey( 'png', $mimes );
	}

	public function test_validate_upload_rejects_missing_file() {
		$result = Attachment::validate_upload( array() );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'cpm_upload_no_file', $result->get_error_code() );
	}

	public function test_validate_upload_rejects_upload_error() {
		$file   = array(
			'name'     => 'spec.pdf',
			'tmp_name' => '/tmp/php123',
			'error'    => UPLOAD_ERR_PARTIAL,
			'size'     => 100,
		);
		$result = Attachment::validate_upload( $file );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'cpm_upload_failed', $result->get_error_code() );
	}

	public function test_validate_upload_rejects_oversized_file() {
		\WP_Mock::userFunction( 'wp_max_upload_size' )->andReturn( 1000 );

		$file = array(
			'name'     => 'spec.pdf',
			'tmp_name' => '/tmp/php123',
			'error'    => 0,
			'size'     => 2000,
		);
		$result = Attachment::validate_upload( $file );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'cpm_upload_too_large', $result->get_error_code() );
	}

	public function test_validate_upload_rejects_bad_type() {
		\WP_Mock::userFunction( 'wp_max_upload_size' )->andReturn( 1000 );
		\WP_Mock::userFunction( 'wp_check_filetype' )->andReturn( array( 'ext' => false, 'type' => false ) );

		$file = array(
			'name'     => 'evil.exe',
			'tmp_name' => '/tmp/php123',
			'error'    => 0,
			'size'     => 100,
		);
		$result = Attachment::validate_upload( $file );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'cpm_upload_bad_type', $result->get_error_code() );
	}

	public function test_validate_upload_accepts_valid_file() {
		\WP_Mock::userFunction( 'wp_max_upload_size' )->andReturn( 1000 );
		\WP_Mock::userFunction( 'wp_check_filetype' )->andReturn(
			array(
				'ext'  => 'pdf',
				'type' => 'application/pdf',
			)
		);

		$file = array(
			'name'     => 'spec.pdf',
			'tmp_name' => '/tmp/php123',
			'error'    => 0,
			'size'     => 100,
			'type'     => 'application/pdf',
		);
		$this->assertTrue( Attachment::validate_upload( $file ) );
	}

	public function test_create_from_upload_returns_error_on_invalid() {
		\WP_Mock::userFunction( 'is_wp_error' )->andReturnUsing(
			function ( $thing ) {
				return $thing instanceof \WP_Error;
			}
		);
		$result = Attachment::create_from_upload( array() );
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_create_from_upload_moves_file_and_creates_attachment() {
		\WP_Mock::userFunction( 'wp_max_upload_size' )->andReturn( 1000 );
		\WP_Mock::userFunction( 'wp_check_filetype' )->andReturn(
			array(
				'ext'  => 'pdf',
				'type' => 'application/pdf',
			)
		);
		\WP_Mock::userFunction( 'remove_filter' )->andReturn( true );
		\WP_Mock::userFunction( 'wp_handle_upload' )->once()->andReturn(
			array(
				'file' => '/srv/uploads/cpm/spec.pdf',
				'url'  => 'http://cpm.test/wp-content/uploads/cpm/spec.pdf',
				'type' => 'application/pdf',
			)
		);
		\WP_Mock::userFunction( 'is_wp_error' )->andReturnUsing(
			function ( $thing ) {
				return $thing instanceof \WP_Error;
			}
		);
		\WP_Mock::userFunction( 'sanitize_file_name' )->andReturnUsing(
			function ( $name ) {
				return $name;
			}
		);
		\WP_Mock::userFunction( 'wp_insert_attachment' )->once()->andReturn( 42 );
		\WP_Mock::userFunction( 'wp_generate_attachment_metadata' )->andReturn( array() );
		\WP_Mock::userFunction( 'wp_update_attachment_metadata' )->once()->with( 42, array() );
		\WP_Mock::userFunction( 'update_post_meta' )->andReturn( true );
		\WP_Mock::userFunction( 'get_comment' )->andReturn( null );

		$file = array(
			'name'     => 'spec.pdf',
			'tmp_name' => '/tmp/php123',
			'error'    => 0,
			'size'     => 100,
			'type'     => 'application/pdf',
		);
		$id   = Attachment::create_from_upload( $file, 15, 20 );
		$this->assertSame( 42, $id );
	}

	public function test_create_from_upload_binds_file_to_comment() {
		\WP_Mock::userFunction( 'wp_max_upload_size' )->andReturn( 1000 );
		\WP_Mock::userFunction( 'wp_check_filetype' )->andReturn(
			array(
				'ext'  => 'pdf',
				'type' => 'application/pdf',
			)
		);
		\WP_Mock::userFunction( 'remove_filter' )->andReturn( true );
		\WP_Mock::userFunction( 'wp_handle_upload' )->andReturn(
			array(
				'file' => '/srv/uploads/cpm/spec.pdf',
				'url'  => 'http://cpm.test/wp-content/uploads/cpm/spec.pdf',
				'type' => 'application/pdf',
			)
		);
		\WP_Mock::userFunction( 'is_wp_error' )->andReturnUsing(
			function ( $thing ) {
				return $thing instanceof \WP_Error;
			}
		);
		\WP_Mock::userFunction( 'sanitize_file_name' )->andReturnUsing(
			function ( $name ) {
				return $name;
			}
		);
		\WP_Mock::userFunction( 'wp_insert_attachment' )->andReturn( 42 );
		\WP_Mock::userFunction( 'wp_generate_attachment_metadata' )->andReturn( array() );
		\WP_Mock::userFunction( 'wp_update_attachment_metadata' );
		\WP_Mock::userFunction( 'update_post_meta' );
		\WP_Mock::userFunction( 'get_comment' )->once()->with( 77 )->andReturn( (object) array( 'comment_ID' => 77 ) );
		\WP_Mock::userFunction( 'get_comment_meta' )->with( 77, '_files', true )->andReturn( array( 5 ) );
		\WP_Mock::userFunction( 'update_comment_meta' )->once()->with( 77, '_files', array( 5, 42 ) );

		$file = array(
			'name'     => 'spec.pdf',
			'tmp_name' => '/tmp/php123',
			'error'    => 0,
			'size'     => 100,
			'type'     => 'application/pdf',
		);
		$id   = Attachment::create_from_upload( $file, 15, 77 );
		$this->assertSame( 42, $id );
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
