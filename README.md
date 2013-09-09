# Database Migrations for the Elefant CMS

This app provides a database migrations framework for the Elefant CMS,
including a simple API for automating migrations as well as a series
of command line options for viewing and applying/reverting changes.

**Status: Beta**

To do:

* Indices
* Auto-incrementing fields

## Installation

Copy the `migrate` app folder into `apps/migrate` and run the following
command to import the database schema:

```bash
./elefant import-db apps/migrate/conf/install_mysql.sql
```

> Note: Replace the database driver with the appropriate one for your site.

## Usage

From the command line:

```bash
# update to the latest revision
./elefant migrate/up myapp\\MyModel

# update to the specified revision
./elefant migrate/up myapp\\MyModel 20130922012345

# revert all revisions
./elefant migrate/down myapp\\MyModel

# revert to the specified revision
./elefant migrate/down myapp\\MyModel 20130922012345

# list all available migrations
./elefant migrate/list

# list all versions of a migration
./elefant migrate/versions myapp\\MyModel

# check which version is current
./elefant migrate/current myapp\\MyModel

# check if the current version is the latest
./elefant migrate/is-latest myapp\\MyModel

# generate a new migration class
./elefant migrate/generate myapp\\MyModel
```

### Defining a new table

Here is a basic migration that creates a table on `migrate/up` and drops
it on `migrate/down`.

```php
<?php

namespace myapp;

class MyModel_20130922012345 extends \Migration {
	public $table = '#prefix#mymodel';
	
	public function up () {
		return $this->table ()
			->column ('id', 'integer', array ('primary' => true))
			->column ('title', 'string', array ('limit' => 72))
			->column ('created', 'datetime', array ('null' => false))
			->column ('body', 'text', array ('null' => false))
			->create ();
	}
	
	public function down () {
		return $this->drop ();
	}
}

?>
```

This migration defines a table with four columns, which will translate
to the following `CREATE TABLE` statement:

```sql
CREATE TABLE #prefix#mymodel (
	id integer primary key,
	title char(72),
	created datetime not null,
	body text not null
);
```

A few things to note:

* Migrations are classes named using the form `MODEL_DATETIME` that extend
  a base `\Migration` class.
* Migrations live in an app's `migrations` folder, and must be named to match
  the class name.
* A migration can set the table name via the `$table` property, or pass the
  name explicitly to methods like `table()` and `drop()`.
* `#prefix#` in database names will be replaced with the value of the `prefix`
  setting from the `[Database]` section of Elefant's global configuration.
* A migration defines two methods: `up()` and `down()` which apply or revert
  the changes for the migration, respectively. Each method should return
  true or false depending on whether they succeeded.

### Migration versioning

We recommend using dates of the form `YYYYMMDDHHIISS` to keep your migrations
in sequential order. The app doesn't enforce a particular naming scheme
however, so you are free to name them using any combination of letters and
numbers, just be aware that it will sort them alphanumerically so you should
name them accordingly to apply them in the right order.

### Available methods

These methods are inherited from `\Migration` for you to use. Methods that
execute SQL statements will return true or false, and `$this->error` will
contain any error messages.

#### `add_column($name, $type = 'char', $options = array ())`

Add a column by altering an existing table. Note that SQLite will always add
the column to the end of the table, even if `'after' => 'colname'` is passed
as an option.

#### `column($name, $type = 'char', $options = array ())`

Adds a column to a `CREATE TABLE` definition. Returns `$this` so you can chain
several `column()` calls together, followed by `create()` to execute the query.

The column type value can be anything supported by your database of choice.
`string` is also an alias for `char`.

Options include:

* `auto-increment` - The column should auto-increment
* `comment` - A comment to add to the column
* `default` - The default value of the column
* `limit` - The character limit of the column
* `null` - The column may or may not be null
* `primary` - The column is a primary key
* `signed` - The column is signed (may contain negative values)
* `unique` - The column value must be unique
* `unsigned` - The column values are unsigned (may contain only positive values)

#### `create()`

Executes a `CREATE TABLE` statement based on the previous calls to `table()`
and `column()`.

#### `driver()`

Returns the PDO driver name for the database connection.

#### `drop($table = null)`

Executes a `DROP TABLE` statement.

#### `drop_column($name)`

Drop a column from the table. Note: Not supported in SQLite.

#### `index($fields)`

Add an index to the table definition with the specified list of field names.

#### `rename_column($old, $new, $type = 'char', $options = array ())`

Rename an existing column. Note: Not supported in SQLite.

#### `run($sql, $params = array ())`

Execute a direct SQL statement.

#### `table($table = null)`

Initializes a new `CREATE TABLE` chain. Returns `$this` so you can chain
several `column()` calls to it, followed by `create()` to execute the query.