<?php
/**
 * Юнит-тесты Team.
 *
 * Матрица tests/покрытие.md: конструктор парсит строку; add()/remove();
 * get_members() возвращает каноничную строку; пустой members; кавычки в user_id
 * (12 не путается с 112).
 *
 * Проверено на стенде: PHP 8.4, PHPUnit 9.6, WP_Mock 1.1.
 */

namespace CPM\v3\Tests;

use CPM\v3\Core\ACL;
use CPM\v3\Core\Team;

class Team_Test extends Cpm_TestCase {

	public function test_constructor_parses_members_string() {
		$team = new Team( '"12":manager,"15":co_worker,"22":co_worker,"33":client' );

		$this->assertSame( ACL::MANAGER, $team->members[12] );
		$this->assertSame( ACL::CO_WORKER, $team->members[15] );
		$this->assertSame( ACL::CO_WORKER, $team->members[22] );
		$this->assertSame( ACL::CLIENT, $team->members[33] );
		$this->assertCount( 4, $team->members );
	}

	public function test_constructor_empty_members() {
		$team = new Team( '' );

		$this->assertSame( array(), $team->members );
		$this->assertSame( '', $team->get_members() );
	}

	public function test_quoted_user_id_does_not_match_substring() {
		$team = new Team( '"12":manager,"112":co_worker' );

		$this->assertArrayHasKey( 12, $team->members );
		$this->assertArrayHasKey( 112, $team->members );
		$this->assertSame( ACL::MANAGER, $team->members[12] );
		$this->assertSame( ACL::CO_WORKER, $team->members[112] );
		$this->assertArrayNotHasKey( 1, $team->members );
	}

	public function test_add_and_remove_members() {
		$team = new Team();
		$team->add( 12, ACL::MANAGER );
		$team->add( 15, ACL::CO_WORKER );

		$this->assertSame( ACL::MANAGER, $team->members[12] );
		$team->remove( 12 );
		$this->assertArrayNotHasKey( 12, $team->members );
		$this->assertSame( ACL::CO_WORKER, $team->members[15] );

		$team->remove( 999 );
		$this->assertCount( 1, $team->members );
	}

	public function test_get_members_returns_canonical_string() {
		$team = new Team();
		$team->add( 12, ACL::MANAGER );
		$team->add( 15, ACL::CO_WORKER );

		$this->assertSame( '"12":manager,"15":co_worker', $team->get_members() );
	}

	public function test_get_members_skips_administrator_and_none() {
		$team = new Team();
		$team->add( 1, ACL::ADMINISTRATOR );
		$team->add( 12, ACL::MANAGER );
		$team->add( 2, ACL::NO_ROLE );

		$this->assertSame( '"12":manager', $team->get_members() );
	}
}
