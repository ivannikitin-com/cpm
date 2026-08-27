<?php
/**
 * Юнит-тесты Project_Entity.
 *
 * Матрица tests/покрытие.md: get_project() создаёт Project по project_id
 * (фабрика/кэш); свойства project_id / project_title / project_slug заполняются.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\Core_Manager;
use CPM\v3\Core\Demo_Entity;
use CPM\v3\Core\Demo_Project_Entity;

class Project_Entity_Test extends Cpm_TestCase {

	public function test_constructor_fills_project_fields() {
		$entity = new Demo_Project_Entity(
			array(
				'id'            => 20,
				'title'         => 'Task',
				'project_id'    => 10,
				'project_title' => 'Acme',
				'project_slug'  => 'acme',
			)
		);

		$this->assertSame( 20, $entity->id );
		$this->assertSame( 'Task', $entity->title );
		$this->assertSame( 10, $entity->project_id );
		$this->assertSame( 'Acme', $entity->project_title );
		$this->assertSame( 'acme', $entity->project_slug );
	}

	public function test_get_project_returns_null_when_project_id_empty() {
		$core = $this->createMock( Core_Manager::class );
		$core->expects( $this->never() )->method( 'load_list' );
		$this->set_core_manager( $core );

		$entity = new Demo_Project_Entity();
		$this->assertNull( $entity->get_project() );
	}

	public function test_get_project_uses_cache_and_load_list() {
		$project = new Demo_Entity( array( 'id' => 10, 'title' => 'Acme' ) );

		$core = $this->createMock( Core_Manager::class );
		$core->expects( $this->once() )
			->method( 'load_list' )
			->with( 'project', array( 'id' => 10 ) )
			->willReturn( array( $project ) );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'wp_cache_get' )->once()->with( 'cpm_project_10' )->andReturn( false );
		\WP_Mock::userFunction( 'wp_cache_set' )->once()->with( 'cpm_project_10', $project );

		$entity = new Demo_Project_Entity( array( 'project_id' => 10 ) );
		$this->assertSame( $project, $entity->get_project() );
	}

	public function test_get_project_returns_cached_instance() {
		$project = new Demo_Entity( array( 'id' => 10 ) );

		$core = $this->createMock( Core_Manager::class );
		$core->expects( $this->never() )->method( 'load_list' );
		$this->set_core_manager( $core );

		\WP_Mock::userFunction( 'wp_cache_get' )->once()->with( 'cpm_project_10' )->andReturn( $project );

		$entity = new Demo_Project_Entity( array( 'project_id' => 10 ) );
		$this->assertSame( $project, $entity->get_project() );
	}
}
