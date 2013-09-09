<?php

require_once ('lib/Autoloader.php');

class MigratorTest extends PHPUnit_Framework_TestCase {
	function setUp () {
		DB::open (array ('master' => true, 'driver' => 'sqlite', 'file' => ':memory:'));
		$sqldata = sql_split (file_get_contents ('apps/migrate/conf/install_sqlite.sql'));
		foreach ($sqldata as $sql) {
			if (! DB::execute ($sql)) {
				die;
			}
		}
	}

	function test_current_is_false () {
		$m = new Migrator ('migrate\Articles');
		$this->assertFalse ($m->current (), 'No current revision should be applied yet');
	}

	function test_is_latest_is_false () {
		$m = new Migrator ('migrate\Articles');
		$this->assertFalse ($m->is_latest (), 'No reivision should be applied yet');
	}

	function test_versions () {
		$m = new Migrator ('migrate\Articles');
		$expected = array ('20130905195757', '20130905195919');
		$this->assertEquals ($expected, $m->versions (), 'Should find 20130905195757 and 20130905195919 for migrate\Articles');
	}

	function test_list_migrations () {
		$migrations = Migrator::list_migrations ();
		$this->assertGreaterThanOrEqual (1, count ($migrations), 'Expecting at least one migration found');
		$this->assertContains ('migrate\Articles', $migrations, 'Should find migrate\Articles migration');
	}

	function test_article_up_and_down () {
		$m = new Migrator ('migrate\Articles');
		$this->assertTrue ($m->up ('20130905195757'), 'First migration should succeed');
		$tables = DB::shift_array ('select name from sqlite_master where type = "table" order by name asc');
		$this->assertContains ('migrate_articles', $tables, 'Table migrate_articles should exist');
		$this->assertEquals ('20130905195757', $m->current ());
		
		$this->assertFalse ($m->up ('20130905195919'), 'Second migration should fail');
		$this->assertEquals ($m->error, '20130905195919: Method not supported in SQLite');
		$this->assertEquals ('20130905195757', $m->current (), 'Should still be at original version');

		$this->assertTrue ($m->down (), 'Revert should succeed');
		$tables = DB::shift_array ('select name from sqlite_master where type = "table" order by name asc');
		$this->assertFalse (in_array ('migrate_articles', $tables), 'Table migrate_articles should be dropped');
		$this->assertEquals ('', $m->current (), 'Current should be empty');
	}
}

?>