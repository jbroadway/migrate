<?php

namespace migrate;

/**
 * Test migration, creates the migrate_articles table.
 */
class Articles_20130905195757 extends \Migration {
	public $table = '#prefix#migrate_articles';

	/**
	 * Create the articles table.
	 */
	public function up () {
		return $this->table ()
			->column ('id', 'integer', array ('primary' => true))
			->column ('title', 'string', array ('limit' => 72))
			->column ('created', 'datetime', array ('null' => false))
			->column ('body', 'text', array ('null' => false))
			->index (array ('created'))
			->create ();
	}
	
	/**
	 * Drop the articles table.
	 */
	public function down () {
		return $this->drop ();
	}
}

?>