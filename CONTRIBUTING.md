Contributing to Typecho
=======================

Thanks for considering contributing to Typecho! There are many ways to contribute to Typecho, and we appreciate all of them.

## Reporting Bugs

If you find a bug in the source code, you can help us by [submitting an issue](https://github.com/typecho/typecho/issues) 
to our [GitHub Repository](https://github.com/typecho/typecho). Even better, you can submit a Pull Request with a fix.

## Contributing Code

If you would like to contribute code to Typecho, please read the following guidelines:

* [Code of Conduct](CODE_OF_CONDUCT.md)
* [Contributing Guide](CONTRIBUTING.md)
* [Coding Style Guide](CODING_STYLE.md)

## Running Tests

Typecho does not use Composer. The test suite runs on a pinned PHPUnit 9.6 phar, which
`tools/phpunit.sh` downloads to `tools/bin/` (checking its sha256) the first time it runs.
You need PHP 7.4 or newer with the `pdo_sqlite`, `sqlite3` and `curl` extensions.

```bash
# Lint every PHP file, as CI does
find . -type f -name '*.php' -print0 | xargs -0 -n1 -P4 php -l -n | (! grep -v "No syntax errors detected" )

# Run the unit, security and database suites
tools/phpunit.sh --testsuite unit,security,db

# Or both at once
cd tools && make test
```

Tests live under `tests/Unit`, `tests/Security` and `tests/Db`, and must keep PHP 7.4 syntax.
Deprecation notices make a test fail, so please fix them instead of silencing them. Tests that need
network access belong in `@group network`; they are skipped by default and can be run with
`tools/phpunit.sh --group network`.

## Translations

Please see [TRANSLATION](https://github.com/typecho/languages) for details.

## Plugin Development

Please see [Plugin Development](http://docs.typecho.org/plugins) for details.

## Theme Development

Please see [Theme Development](http://docs.typecho.org/themes) for details.

## Community

* [Telegram Channel](https://t.me/typechodev)
* [Homepage](http://typecho.org/)
* [Documents](http://docs.typecho.org/)
* [Community](http://forum.typecho.org/)
* [Download](http://typecho.org/download)