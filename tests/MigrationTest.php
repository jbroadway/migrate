<?php

require_once ('lib/Autoloader.php');

class MigrationTest extends PHPUnit_Framework_TestCase {
	function test_driver () {
		$m = new Migration;

		$this->assertEquals ('sqlite', $m->driver ());
	}
}

?>