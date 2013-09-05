<?php

/**
 * Print whether a given migration has new updates to apply.
 */

if (! $this->cli) {
	die ('Must be run from the command line.');
}

$page->layout = false;

if (! isset ($_SERVER['argv'][2])) {
	Cli::out ('Usage: elefant migrate/is-latest <migration-name>', 'info');
	die;
}

$name = $_SERVER['argv'][2];

$m = new Migrator ($name);

if (! $m->is_latest ()) {
	Cli::out ($name . ' has new updates.', 'info');
} else {
	Cli::out ($name . ' is up-to-date.');
}

?>