<?php

/**
 * Generate a new migration class file.
 */

if (! $this->cli) {
	die ('Must be run from the command line.');
}

$page->layout = false;

if (! isset ($_SERVER['argv'][2])) {
	Cli::out ('Usage: elefant migrate/generate <migration-name>', 'info');
	die;
}

$name = $_SERVER['argv'][2];

list ($app, $class) = explode ('\\', $name);
$date = gmdate ('YmdHis');

$file = sprintf ('apps/%s/migrations/%s_%s.php', $app, $class, $date);
$path = dirname ($file);

if (! is_dir ($path)) {
	mkdir ($path, 0777, true);
}

file_put_contents (
	$file,
	$tpl->render (
		'migrate/generate',
		array (
			'app' => $app,
			'class' => $class,
			'date' => $date,
			'open_tag' => '<?php',
			'close_tag' => '?>',
			'backslash' => '\\'
		)
	)
);

Cli::out ('Generated migration in ' . $file);

?>