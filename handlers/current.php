<?php

/**
 * Print the currently applied revision for a given migration.
 */

if (! $this->cli) {
	die ('Must be run from the command line.');
}

$page->layout = false;

if (! isset ($_SERVER['argv'][2])) {
	Cli::out ('Usage: elefant migrate/current <migration-name>', 'info');
	die;
}

$name = $_SERVER['argv'][2];

$m = new Migrator ($name);

$current = $m->current ();
if (! $current) {
	Cli::out ('No revision applied.', 'info');
} else {
	Cli::out ($current);
}

?>