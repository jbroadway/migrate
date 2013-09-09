<?php

/**
 * Extend this class to create migrations for your models.
 *
 * Usage:
 *
 *     <?php // apps/myapp/migrations/MyModel_20130922012345.php
 *     
 *     namespace myapp; // required
 *     
 *     class MyModel_20130922012345 extends \Migration {
 *         public $table = '#prefix#mymodel';
 *         
 *         public function up () {
 *             return $this->table ()
 *                 ->column ('id', 'integer', array ('primary' => true))
 *                 ->column ('title', 'string', array ('limit' => 72))
 *                 ->column ('created', 'datetime', array ('null' => false))
 *                 ->column ('body', 'text', array ('null' => false))
 *                 ->create ();
 *         }
 *         
 *         public function down () {
 *             return $this->drop ();
 *         }
 *     }
 *     
 *     ?>
 */
class Migration {
	/**
	 * Name of the database table. Use #prefix# at the start to automatically
	 * add Elefant's database prefix.
	 */
	public $table = null;

	/**
	 * The error message if an error occurred applying a change.
	 */
	public $error = null;

	protected $_driver = null;

	protected $_columns = array ();

	protected $_indices = array ();

	protected $_types = array (
		'string' => 'char',
	);

	/**
	 * Constructor method.
	 */
	public function __construct () {
		$this->_driver = $this->driver ();
	}

	/**
	 * Get the database driver type;
	 */
	public function driver () {
		$db = DB::get_connection (1);
		return $db->getAttribute (PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Begin a new migration action on a table, and optionally
	 * set the table name.
	 */
	public function table ($table = null) {
		$this->table = $table ? $table : $this->table;
		$this->_columns = array ();
		$this->_indices = array ();
		return $this;
	}

	/**
	 * Execute a CREATE TABLE statement based on the current
	 * columns/indices.
	 */
	public function create () {
		$sql = 'create table ' . Model::backticks ($this->table) . ' (';
		$sep = '';
		$sequences = array ();

		// Build the column list
		foreach ($this->_columns as $col) {
			$sql .= $sep . $this->define_column ($col['name'], $col['type'], $col['options']);
			$sep = ",\n";

			// Check for sequences in PostgreSQL
			if ($this->_driver === 'pgsql' && isset ($col['options']['auto-increment'])) {
				$sequences[] = 'create sequence ' . Model::backticks ($this->table . '_' . $col['name'] . '_seq');
			}
		}

		$sql .= ')';

		// Create sequences for PostgreSQL auto-incrementing columns		
		foreach ($sequences as $sequence) {
			if (! DB::execute ($sequence)) {
				$this->error = DB::error ();
				return false;
			}
		}

		// Create the table
		if (! DB::execute ($sql)) {
			$this->error = DB::error ();
			return false;
		}

		// Build indices
		foreach ($this->_indices as $name => $index) {
			$create = 'create index ' . Model::backticks ($name)
				. ' on ' . Model::backticks ($this->table) . ' ('
				. join (', ', array_map ('Model::backticks', $index['fields'])) . ')';

			if (! DB::execute ($create)) {
				$this->error = DB::error ();
				return false;
			}
		}

		return true;
	}

	/**
	 * Execute a DROP TABLE statement.
	 */
	public function drop ($table = null) {
		$table = $table ? $table : $this->table;
		if (! DB::execute ('drop table ' . Model::backticks ($table))) {
			$this->error = DB::error ();
			return false;
		}
		return true;
	}

	/**
	 * Add a column to the table definition. You can chain a series of
	 * `column()` calls and add a `->create()` at the end to execute
	 * the creation of a new database table.
	 */
	public function column ($name, $type = 'char', $options = array ()) {
		$this->_columns[$name] = array (
			'name' => $name,
			'type' => $type,
			'options' => $options
		);
		return $this;
	}

	/**
	 * Add an index to the table definition.
	 */
	public function index ($fields) {
		$fields = is_array ($fields) ? $fields : array ($fields);
		$name = $this->table . '_' . join ('_', $fields) . '_idx';
		$this->_indices[$name] = array (
			'name' => $name,
			'fields' => $fields
		);
		return $this;
	}

	/**
	 * Execute an SQL statement directly.
	 */
	public function run ($sql, $params = array ()) {
		if (! DB::execute ($sql, $params)) {
			$this->error = DB::error ();
			return false;
		}
		return true;
	}

	/**
	 * Create the SQL declaration for a single column.
	 */
	public function define_column ($name, $type = 'char', $options = array ()) {
		$opts = '';

		foreach ($options as $opt => $val) {
			switch ($opt) {
				case 'default':
					$opts .= ' default ' . (is_numeric ($val)
						? $val
						: "'" . str_replace ("'", "''", $val) . "'");
					break;
				case 'limit':
					if (is_array ($val)) {
						$opts = '(' . $val[0] . ',' . $val[1] . ')' . $opts;
					} else {
						$opts = '(' . $val . ')' . $opts;
					}
					break;
				case 'unsigned':
				case 'signed':
					$opts .= ' ' . $opt;
					break;
				case 'null':
					$opts .= ' ' . ($val ? 'null' : 'not null');
					break;
				case 'primary':
					$opts .= ' primary key';
					break;
				case 'comment':
					$opts .= " comment '" . str_replace ("'", "''", $val) . "'";
					break;
				case 'auto-increment':
					if ($this->_driver === 'sqlite') {
						$type = 'integer';
						if (! isset ($options['primary'])) {
							$opts .= ' primary key';
						}
					} elseif ($this->_driver === 'pgsql') {
						// Assume for now we will create the sequence later
						$opts .= " nextval('" . $this->table . '_' . $name . "_seq')";
					} elseif ($this->_driver === 'mysql') {
						$opts .= ' auto_increment';
					}
					break;
			}
		}

		return sprintf (
			'%s %s%s',
			Model::backticks ($name),
			isset ($this->_types[$type]) ? $this->_types[$type] : $type,
			$opts
		);
	}

	/**
	 * Add a new column. Note: SQLite will always add the column
	 * to the end of the table, even if `'after' => 'colname'` is
	 * specified.
	 */
	public function add_column ($name, $type = 'char', $options = array ()) {
		$sql = 'alter table ' . Model::backticks ($this->table) . ' add column ';
		$sql .= $this->define_column ($name, $type, $options);
		if ($this->_driver !== 'sqlite') {
			$sql .= isset ($options['after']) ? ' after ' . Model::backticks ($options['after']) : '';
		}
		
		return $this->run ($sql);
	}

	/**
	 * Drop a column. Note: Not supported in SQLite.
	 */
	public function drop_column ($name) {
		if ($this->_driver === 'sqlite') {
			$this->error = 'Method not supported in SQLite';
			return false;
		}

		return $this->run (sprintf (
			'alter table %s drop column %s',
			Model::backticks ($this->table),
			Model::backticks ($name)
		));
	}

	/**
	 * Rename a column. Note: Not supported in SQLite.
	 */
	public function rename_column ($old, $new, $type = 'char', $options = array ()) {
		if ($this->_driver === 'sqlite') {
			$this->error = 'Method not supported in SQLite';
			return false;
		}

		return $this->run (sprintf (
			'alter table %s rename column %s %s' ,
			Model::backticks ($this->table),
			Model::backticks ($old),
			$this->define_column ($new, $type, $options)
		));
	}
}

?>