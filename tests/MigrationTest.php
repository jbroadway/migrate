<?php

require_once ('lib/Autoloader.php');

class MigrationTest extends PHPUnit_Framework_TestCase {
	function setUp () {
		DB::open (array ('master' => true, 'driver' => 'sqlite', 'file' => ':memory:'));
		$sqldata = sql_split (file_get_contents ('apps/migrate/conf/install_sqlite.sql'));
		foreach ($sqldata as $sql) {
			if (! DB::execute ($sql)) {
				die;
			}
		}
	}

	function test_driver () {
		$m = new Migration;

		$this->assertEquals ('sqlite', $m->driver ());
	}

	function test_define_column () {
		$m = new Migration;
		
		$this->assertEquals ('`a` char',					$m->define_column ('a'));
		$this->assertEquals ('`a` char(3)',					$m->define_column ('a', 'string', array ('limit' => 3)));
		$this->assertEquals ('`a` int',						$m->define_column ('a', 'int'));
		$this->assertEquals ('`a` float(5,2)',				$m->define_column ('a', 'float', array ('limit' => array (5, 2))));
		$this->assertEquals ('`a` int signed',				$m->define_column ('a', 'int', array ('signed' => true)));
		$this->assertEquals ('`a` int unsigned',			$m->define_column ('a', 'int', array ('unsigned' => true)));
		$this->assertEquals ('`a` int null',				$m->define_column ('a', 'int', array ('null' => true)));
		$this->assertEquals ('`a` int not null',			$m->define_column ('a', 'int', array ('null' => false)));
		$this->assertEquals ('`a` integer primary key',		$m->define_column ('a', 'integer', array ('primary' => true)));
		$this->assertEquals ('`a` int default 2',			$m->define_column ('a', 'int', array ('default' => 2)));
		$this->assertEquals ("`a` char(1) default 'a'",		$m->define_column ('a', 'char', array ('limit' => 1, 'default' => 'a')));
		$this->assertEquals (
			"`a` text default 'isn''t this cool?'",
			$m->define_column ('a', 'text', array ('default' => "isn't this cool?"))
		);
		$this->assertEquals (
			"`a` int comment 'isn''t this cool?'",
			$m->define_column ('a', 'int', array ('comment' => "isn't this cool?"))
		);
	}
}

?>