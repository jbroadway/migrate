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
		'integer' => 'integer',
		'string' => 'char',
		'text' => 'text',
		'date' => 'date',
		'time' => 'time',
		'datetime' => 'datetime',
		'timestamp' => 'timestamp'
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

		// columns
		foreach ($this->_columns as $col) {
			$sql .= $sep . $this->define_column ($col['name'], $col['type'], $col['options']);
			$sep = ",\n";
		}

		$sql .= ')';
		
		// TODO: Indices

		if (! DB::execute ($sql)) {
			$this->error = DB::error ();
			return false;
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
	 * Execute an ALTER TABLE statement based on the current
	 * columns/indices.
	 */
	public function alter () {
		$sql = 'alter table ' . Model::backticks ($this->table);

		// TODO: Columns
		
		// TODO: Indices

		if (! DB::execute ($sql)) {
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
	public function column ($name, $type, $options) {
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
		$name = $this->table . '_' . join ('_', $fields);
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
	public function define_column ($name, $type, $options) {
		// TODO: Finish definitions (type mapping, options)
		return Model::backticks ($name) . ' ' . $this->_types[$type];
	}

	/**
	 * Add a new column.
	 */
	public function add_column ($name, $type = 'string', $options = array ()) {
		$sql = 'alter table ' . Model::backticks ($this->table) . ' add column ';
		$sql .= $this->define_column ($name, $type, $options);
		$sql .= isset ($options['after']) ? ' after ' . Model::backticks ($options['after']) : '';
		
		return $this->run ($sql);
	}

	/**
	 * Drop a column.
	 */
	public function drop_column ($name) {
		// TODO: Fix drop column
		return $this->run (sprintf (
			'alter table %s drop column %s',
			Model::backticks ($this->table),
			Model::backticks ($name)
		));
	}

	/**
	 * Rename a column.
	 */
	public function rename_column ($old, $new, $type, $options) {
		DB::beginTransaction ();

		$options['after'] = $old;

		if (! $this->add_column ($new, $type, $options)) {
			DB::rollback ();
			return false;
		}
		
		if (! $this->run (sprintf (
			'update %s set %s = %s',
			Model::backticks ($this->table),
			Model::backticks ($new),
			Model::backticks ($old)
		))) {
			DB::rollback ();
			return false;
		}
		
		if (! $this->drop_column ($old)) {
			DB::rollback ();
			return false;
		}

		DB::commit ();
		return true;
	}
}

?>