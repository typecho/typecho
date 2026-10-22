#!/usr/bin/env bash
# Run the test suite with a pinned PHPUnit phar.
# Usage: tools/phpunit.sh [phpunit args...], e.g. tools/phpunit.sh --testsuite unit,security,db
set -euo pipefail

PHPUNIT_VERSION=9.6.38
PHPUNIT_SHA256=6b5235f282c6ccab3674ac27c5b1ddbd1da838e74bf07002ddcfd030d31298d7
PHPUNIT_URL="https://phar.phpunit.de/phpunit-${PHPUNIT_VERSION}.phar"

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHAR="$ROOT/tools/bin/phpunit.phar"

sha256() {
    if command -v shasum >/dev/null 2>&1; then
        shasum -a 256 "$1" | cut -d' ' -f1
    else
        sha256sum "$1" | cut -d' ' -f1
    fi
}

if [ ! -f "$PHAR" ] || [ "$(sha256 "$PHAR")" != "$PHPUNIT_SHA256" ]; then
    mkdir -p "$(dirname "$PHAR")"
    echo "Downloading PHPUnit $PHPUNIT_VERSION..." >&2
    curl -fsSL -o "$PHAR.tmp" "$PHPUNIT_URL"
    actual="$(sha256 "$PHAR.tmp")"
    if [ "$actual" != "$PHPUNIT_SHA256" ]; then
        rm -f "$PHAR.tmp"
        echo "sha256 mismatch for $PHPUNIT_URL: expected $PHPUNIT_SHA256, got $actual" >&2
        exit 1
    fi
    mv "$PHAR.tmp" "$PHAR"
fi

# phpunit.xml.dist and its relative paths are resolved from the repository root
cd "$ROOT"
exec php "$PHAR" "$@"
