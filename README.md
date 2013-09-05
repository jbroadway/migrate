# Database Migrations for the Elefant CMS

This app provides a database migrations framework for the Elefant CMS,
including a simple API for automating migrations as well as a series
of command line options for viewing and applying/reverting changes.

Status: Pre-Alpha

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
