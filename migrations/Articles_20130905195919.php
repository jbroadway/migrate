<?php

namespace migrate;

/**
 * Test migration. Renames the title column to headline.
 */
class Articles_20130905195919 extends \Migration {
	public $table = '#prefix#migrate_articles';

	/**
	 * Rename the title column to headline.
	 */
	public function up () {
		return $this->rename_column ('title', 'headline', 'string', array ('limit' => 72));
	}

	/**
	 * Rename the headline column to title.
	 */
	public function down () {
		return $this->rename_column ('headline', 'title', 'string', array ('limit' => 72));
	}
}

?>