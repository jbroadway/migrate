<?php

/**
 * Revert to the specified revision, or all of them.
 */

if (! $this->cli) {
	die ('Must be run from the command line.');
}

$page->layout = false;

if (! isset ($_SERVER['argv'][2])) {
	Cli::out ('Usage: elefant migrate/down <migration-name> <revision>', 'info');
	die;
}

$name = $_SERVER['argv'][2];

if (isset ($_SERVER['argv'][3])) {
	$revision = $_SERVER['argv'][3];
} else {
	$revision = null;
}

$m = new Migrator ($name);

if (! $m->down ($revision)) {
	Cli::out ($m->error, 'error');
} else {
	Cli::out ($m->name . ' is now at ' . $m->current ());
}

?>