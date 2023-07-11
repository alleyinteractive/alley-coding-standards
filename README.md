# Alley Coding Standards

This is a PHPCS ruleset for [Alley Interactive](https://alley.com).

## Installation

To use this standard in a project, declare it as a dependency.

```
composer require alleyinteractive/alley-coding-standards
```

This will install the latest compatible version of PHPCS, WPCS, and VIPCS to your vendor directory in order to run sniffs locally.

You can also manually add this to your project's composer.json file as part of the `require` property:

```
"require": {
    "alleyinteractive/alley-coding-standards": "^1.0"
}
```

## Using PHPCS

To use this standard with `phpcs` directly from your command line, use the command:

```
vendor/bin/phpcs --standard=Alley-Interactive .
```

Alternatively, you can set this as a composer script, which will automatically reference the correct version of `phpcs` and the dependent standards.

```
"scripts": {
    "phpcs" : "phpcs --standard=Alley-Interactive ."
}
```

Then use the following command:

```
composer run phpcs
```

You can also pass arguments to the composer phpcs script, following a `--` operator like this:

```
composer run phpcs -- --report=summary
```

## Extending the ruleset

You can create a custom ruleset for your project that extends or customizes these rules by creating your own  `phpcs.xml` or `phpcs.xml.dist` file in your project, which references these rules, like this:

```xml
<?xml version="1.0"?>
<ruleset>
  <description>Example project ruleset</description>

  <!-- Include Alley Rules -->
  <rule ref="Alley-Interactive" />

  <!-- Project customizations go here -->
</ruleset>
```

## Using Alley Coding Standards on PHP 8.1

If you plan on using this ruleset on PHP 8.1, you will need to make some
modifications to your Composer configuration. For some context, the current
version of this ruleset requires `wp-coding-standards/wpcs` 2.3. This package is
not compatible with PHP 8.1 and runs into some issues when being used. Until
`wp-coding-standards/wpcs` 3.0 is released we can use a forked version of the
package that is compatible with PHP 8.1.

### 1. Add the Repository

Add the Composer repository to your project's `composer.json` file:

```json
{
    "repositories": {
        "wpcs": {
            "type": "vcs",
            "url": "https://github.com/alleyinteractive/WordPress-Coding-Standards"
        }
    }
}
```

### 2. Switch to the Forked Version

Add the following to your `composer.json` file `autoload-dev` section:

```json
{
    "require-dev": {
        "alleyinteractive/alley-coding-standards": "^1.0",
        "wp-coding-standards/wpcs": "dev-php-8-1 as 2.3.x-dev"
    },
}
```

### 3. Run `composer update`

If you run into any problems, please open an issue. After
`wp-coding-standards/wpcs` 3.0 is released we will remove the above fork from
our projects and rely on the official release.

## Change Log

This project adheres to [Keep a CHANGELOG](https://keepachangelog.com/en/1.0.0/).

### 1.0.0

- No changes, tagging a stable release of Alley Coding Standards.

### 0.4.1

- Upgrading to `automattic/vipwpcs` v2.3.3 and `dealerdirect/phpcodesniffer-composer-installer` v0.7.2.

### 0.4.0

- Add PHPCompatibilityWP sniffs to our rules, configured for PHP 8.0+
- Make template-parts rule checking more ambiguous to better support scanning standalone plugins and themes
- Added `static analysis` keyword to Composer to promote package to be installed with `--dev`.

### 0.3.0

- Add PHPCompatibilityWP standard as a dependency (#9)
- Exclude plugin template parts from WordPress.NamingConventions.PrefixAllGlobals sniff (#11)
- Remove 'wp_cache_set' from forbidden functions (#12)

### 0.2.0

- Sniff name changed to Alley-Interactive.
- Composer package renamed to `alleyinteractive/alley-coding-standards`.
- Allow short ternary syntax (#6)

### 0.1.0

- Initial release.
