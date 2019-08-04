# Contao Exec
This package allows to execute PHP code in the context of a Contao application, directly from the command line.

It provides two ways to do so:

* **A [PsySH](http://psysh.org)-based REPL** for tinkering around or getting some quick insight into the state of your Contao project.

* **An [`eval`](https://www.php.net/manual/function.eval.php) command** to get results of a program in a certain data exchange format — useful for getting access to data from outside of Contao.

## Installation
```bash
composer require loilo/contao-exec-bundle
```

## Usage
### REPL
You can open up the REPL with the `debug:repl` command:

```bash
vendor/bin/contao-console debug:repl
```

This will throw you into a nice REPL with the Contao framework loaded, all models, DCAs etc. available.

Also, [the `db()` helper](https://github.com/loilo/contao-illuminate-database-bundle) will be loaded into the namespace automatically if you have it installed, so you can do some quick fiddling right away:

```php
// Get the URL to the newest page
db()->from('page')->asModel()->orderBy('id', 'desc')->first()->getAbsoluteUrl()
```

As usual, all options are available via

```bash
vendor/bin/contao-console help debug:repl
```

### Eval
The `contao:eval` command takes some PHP code, passes it to the REPL and outputs the result.

If you're using the terminal manually, you probably want to use `debug:repl` instead. However, `contao:eval` can be a great tool to access Contao data from other processes (e.g. Node.js) etc.

Therefore, it does not only have the default `dump` output formatting for human-readable data but also some others, including `json`:

```bash
# Get ID and title of the newest page as a JSON object
# This example requires the loilo/contao-illuminate-database-bundle to be installed
vendor/bin/contao-console contao:eval 'db()\
  ->from("page")\
  ->select("id", "title")\
  ->orderBy("id", "desc")\
  ->first()'\
  --format json\
  --no-ansi

# > {"id":1,"title":"Home"}
```

Again, use `vendor/bin/contao-console help contao:eval` to see what's possible with different options.