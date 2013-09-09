<?php

/**
 * Handles upgrading and downgrading between migrations.
 *
 * Usage:
 *
 *     <?php
 *     
 *     $m = new Migrator ('migrate\Articles');
 *     
 *     echo $m->current (); // 20130924012345
 *     
 *     if (! $m->is_latest ()) {
 *         $m->up ();
 *     }
 *     
 *     ?>
 */
class Migrator {
	public $name;
	
	/**
	 * Constructor method.
	 */
	public function __construct ($name) {
		$this->name = $name;
	}
	
	/**
	 * Find the app name for a migration.
	 */
	public function filename ($name, $revision) {
		if (strpos ($name, '\\') === false) {
			return false;
		}

		list ($app, $class) = explode ('\\', $name, 2);
		return 'apps/' . $app . '/migrations/' . $class . '_' . $revision . '.php';
	}

	/**
	 * Returns the currently applied revision.
	 */
	public function current () {
		$current = DB::shift (
			'select `revision` from `#prefix#migrations` where `name` = ?',
			$this->name
		);
		return $current ? $current : false;
	}

	/**
	 * List all migrations.
	 */
	public static function list_migrations () {
		$files = glob ('apps/*/migrations/*.php');
		$migrations = array ();

		foreach ($files as $file) {
			if (preg_match ('/apps\/(.+)\/migrations\/(.+)_[a-zA-Z0-9]+\.php/', $file, $regs)) {
				$migration = $regs[1] . '\\' . $regs[2];
				if (! in_array ($migration, $migrations)) {
					$migrations[] = $migration;
				}
			}
		}

		return $migrations;
	}

	/**
	 * List all versions available for the current migration.
	 */
	public function versions () {
		if (strpos ($this->name, '\\') !== false) {
			list ($app, $class) = explode ('\\', $this->name, 2);
			$files = glob ('apps/' . $app . '/migrations/' . str_replace ('\\', '/', $class) . '_*.php');
		} else {
			$files = glob ('apps/*/migrations/' . str_replace ('\\', '/', $this->name) . '_*.php');
		}
		$versions = array ();
		foreach ($files as $file) {
			if (preg_match ('/_([a-zA-Z0-9]+)\.php$/', $file, $regs)) {
				$versions[] = $regs[1];
			}
		}
		return $versions;
	}

	/**
	 * Returns whether the currently applied revision is the latest.
	 */
	public function is_latest () {
		$current = $this->current ();
		if ($current === false) {
			return false;
		}
		
		$versions = $this->versions ();
		if (empty ($versions)) {
			return false;
		}
		
		$latest = array_pop ($versions);
		if ($latest > $current) {
			return false;
		}
		return true;
	}

	/**
	 * Apply revisions up to the one specified, or all until up-to-date.
	 */
	public function up ($revision = null) {
		$current = $this->current ();
		$versions = $this->versions ();

		foreach ($versions as $version) {
			if ($version <= $current) {
				// Don't apply up() on older revisions
				continue;
			} elseif ($revision !== null && $version > $revision) {
				// Don't apply up() on newer revisions
				break;
			}
			
			$filename = $this->filename ($this->name, $version);
			if (! $filename) {
				$this->error = 'No app name specified.';
				return false;
			} elseif (! file_exists ($filename)) {
				$this->error = 'File not found: ' . $filename;
				return false;
			}

			require_once ($filename);
			$class = $this->name . '_' . $version;
			$m = new $class;
			if (! $m->up ()) {
				$this->error = $version . ': ' . $m->error;
				return false;
			} else {
				if ($current === false) {
					DB::execute (
						'insert into `#prefix#migrations` values (?, ?, ?)',
						$this->name,
						$version,
						gmdate ('Y-m-d H:i:s')
					);
				} else {
					DB::execute (
						'update `#prefix#migrations set `revision` = ?, `applied` = ? where `name` = ?', 
						$version,
						gmdate ('Y-m-d H:i:s'),
						$this->name
					);
				}
				$current = $version;
			}
		}
		return true;
	}

	/**
	 * Revert revisions to the one specified, or until all are reverted.
	 */
	public function down ($revision = null) {
		$current = $this->current ();
		$versions = array_reverse ($this->versions ());

		foreach ($versions as $vkey => $version) {
			if ($current !== false && $version > $current) {
				// Don't apply down() on unapplied newer revisions
				continue;
			} elseif ($revision !== null && $version <= $revision) {
				// Don't apply down() on specified revision or earlier
				break;
			}

			// The previous revision
			$previous = isset ($versions[$vkey + 1]) ? $versions[$vkey + 1] : '';
			
			$filename = $this->filename ($this->name, $version);
			if (! $filename) {
				$this->error = 'No app name specified.';
				return false;
			} elseif (! file_exists ($filename)) {
				$this->error = 'File not found: ' . $filename;
				return false;
			}

			require_once ($filename);
			$class = $this->name . '_' . $version;
			$m = new $class;
			if (! $m->down ()) {
				$this->error = $version . ': ' . $m->error;
				return false;
			} else {
				DB::execute (
					'update `#prefix#migrations` set `revision` = ?, `applied` = ? where `name` = ?', 
					$previous,
					gmdate ('Y-m-d H:i:s'),
					$this->name
				);
				$current = $version;
			}
		}
		return true;
	}
}

?>