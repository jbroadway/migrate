<?php

/**
 * Print a list of available migrations.
 */

if (! $this->cli) {
	die ('Must be run from the command line.');
}

$page->layout = false;

$migrations = Migrator::list_migrations ();

foreach ($migrations as $name) {
	Cli::out ($name);
}

?>