<?php

/**
 * Print all revisions for a given migration.
 */

if (! $this->cli) {
	die ('Must be run from the command line.');
}

$page->layout = false;

if (! isset ($_SERVER['argv'][2])) {
	Cli::out ('Usage: elefant migrate/versions <migration-name>', 'info');
	die;
}

$name = $_SERVER['argv'][2];

$m = new Migrator ($name);

$versions = $m->versions ();
$current = $m->current ();
foreach ($versions as $version) {
	if ($current == $version) {
		Cli::out ($version . '*', 'info');
	} else {
		Cli::out ($version);
	}
}

?>