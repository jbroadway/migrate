<?php

require_once ('lib/Autoloader.php');

class MigratorTest extends PHPUnit_Framework_TestCase {
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
}

?>