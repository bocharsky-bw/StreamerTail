# StreamerTail

Display the last part of a data which fetched by specified SQL query.

* [Installation](#installation)
* [Usage](#usage)
* [Option list](#option-list)
* [Contribution](#contribution)

## Installation

First of all, install StreamerTail globally with [Composer][3]:

```bash
$ composer global require bocharsky-bw/streamertail
```

Be sure to have composer binaries in your `$PATH`:

```bash
export PATH=${PATH}:${HOME}/.composer/vendor/bin;
```

or make symbolic link to executable file manually:

```bash
ln -s ~/.composer/vendor/bin/streamer /usr/local/bin/streamer
```

## Usage

To tail and watching `table_name` table in `db_name` MySQL database use
next command with `-f` option.

```bash
./bin/streamer tail "SELECT * FROM db_name.table_name" -f
```

> By default, assuming MySQL database usage with default credentials:
  `mysql://root@localhost` (`root` user with empty password). That's why
  you need to point the database name in specified SQL query. Use the `--url`
  option to set custom database URL with default database name like:
  `--url=mysql://root@localhost/db_name`.

## Option list

List of available options for `tail` command:

* `-f`, `--force`         - Wait for additional data to be appended to the
  table that observed with specified SQL query.
* `-u`, `--url[=URL]`     - Database config URL. [default: "mysql://root@localhost"].
  See Doctrine's [configuration][4] page for more info about URL syntax.
* `-s`, `--sleep[=SLEEP]` - Number in seconds to refresh data. [default: 1].
  Time in seconds before new data will be re-fetched with specified SQL query.
* `-l`, `--limit[=LIMIT]` - Number of latest rows to limit from start. [default: 3]

To see the full list of available options for `tail` command run next:

```bash
./bin/streamer tail -h
```

## Contribution

Feel free to submit an [Issue][1] or create a [Pull Request][2] if you find
a bug or just want to propose an improvement suggestion.

In order to propose a new feature, the best way is to submit an [Issue][1]
and discuss it first.

[Move UP](#streamertail)


[1]: https://github.com/bocharsky-bw/StreamerTail/issues
[2]: https://github.com/bocharsky-bw/StreamerTail/pulls
[3]: https://getcomposer.org/
[4]: http://doctrine-orm.readthedocs.org/projects/doctrine-dbal/en/latest/reference/configuration.html
