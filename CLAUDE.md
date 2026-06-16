# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Repository Is

A PHPCS (PHP CodeSniffer) ruleset package for Alley Interactive. It defines the `Alley-Interactive` coding standard, which extends `WordPress-VIP-Go` with Alley-specific customizations. When installed as a Composer dependency in other projects, it provides a `phpcs --standard=Alley-Interactive` command.

## Commands

```bash
# Install dependencies
composer install

# Run all checks (phpcs on test files + phpunit)
composer test

# Run PHPCS on test files only
composer phpcs

# Run PHPCS on fixture files (to verify pass/fail expectations manually)
composer phpcs:fixtures

# Run PHPUnit tests
composer phpunit

# Auto-fix PHPCS violations in test files
composer phpcbf
```

To run a single PHPUnit test:
```bash
vendor/bin/phpunit --filter test_passing_fixtures
vendor/bin/phpunit --filter test_failing_fixtures
```

## Architecture

### Ruleset: `Alley-Interactive/ruleset.xml`

The central artifact. It extends `WordPress-VIP-Go` (which itself includes `WordPress`, `WordPress-Extra`, `WordPressVIPMinimum`) and applies Alley-specific overrides.

### Custom Sniffs: `Alley-Interactive/Sniffs/PHP/`

Custom sniffs live here under the `Alley\Sniffs\PHP` namespace.

Custom sniff sources are referenced in PHPCS output as `Alley.PHP.<SniffName>.<ErrorCode>`.

### Test System: `tests/`

`FixtureTest.php` uses a data-provider pattern against fixture files:

- **`tests/fixtures/pass/*.php`** — files that must produce zero PHPCS errors
- **`tests/fixtures/fail/*.php`** — files that must produce at least one PHPCS error
- **`tests/fixtures/fail/expectations/*.php`** — return arrays of expected PHPCS error source strings (e.g. `'Alley.PHP.StrictTypeDeclarationSpacing.SpaceAfterEquals'`); the test fails if an unexpected error appears OR if an expected error is absent

Expectations files are matched by filename to their corresponding fail fixture. An expectations file is optional — without one, the test only asserts that _some_ error occurs.

`PhpcsHelper` trait (`tests/PhpcsHelper.php`) runs PHPCS via `shell_exec` with `--report=json` and parses the result for assertions.

### Adding a New Rule or Sniff

1. For ruleset-only changes: edit `Alley-Interactive/ruleset.xml`
2. For a new custom sniff: add a class to `Alley-Interactive/Sniffs/PHP/` extending `WordPressCS\WordPress\Sniff`
3. Add a fixture to `tests/fixtures/pass/` and/or `tests/fixtures/fail/`
4. If adding a fail fixture, add a corresponding `tests/fixtures/fail/expectations/` file returning an array of expected error source strings
