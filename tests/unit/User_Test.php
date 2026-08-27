<?php
/**
 * Юнит-тесты User.
 *
 * Матрица tests/покрытие.md: конструктор не вызывает load(); get_name()/get_avatar()
 * лениво грузят; load() не найден → BadUserException.
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\ACL;
use CPM\v3\Core\BadUserException;
use CPM\v3\Core\User;

class User_Test extends Cpm_TestCase {

	public function test_constructor_does_not_call_load() {
		\WP_Mock::userFunction( 'get_userdata' )->never();
		\WP_Mock::userFunction( 'get_avatar_url' )->never();

		$user = new User( 12, ACL::MANAGER );

		$this->assertSame( 12, $user->id );
		$this->assertSame( ACL::MANAGER, $user->role );
		$this->assertSame( '', $user->name );
		$this->assertSame( '', $user->avatar );
	}

	public function test_get_name_and_get_avatar_load_lazily() {
		$wp_user                = new \stdClass();
		$wp_user->display_name  = 'Ivan';

		\WP_Mock::userFunction( 'get_userdata' )->once()->with( 12 )->andReturn( $wp_user );
		\WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		\WP_Mock::userFunction( 'get_avatar_url' )->once()->with( 12 )->andReturn( 'https://example.test/a.png' );

		$user = new User( 12, ACL::CLIENT );

		$this->assertSame( 'Ivan', $user->get_name() );
		$this->assertSame( 'https://example.test/a.png', $user->get_avatar() );
		$this->assertSame( 'Ivan', $user->get_name() );
	}

	public function test_load_throws_bad_user_exception_when_not_found() {
		\WP_Mock::userFunction( 'get_userdata' )->once()->with( 99 )->andReturn( false );

		$user = new User( 99, ACL::CO_WORKER );

		$this->expectException( BadUserException::class );
		$user->get_name();
	}
}
