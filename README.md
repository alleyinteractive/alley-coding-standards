# Alley Coding Standards

This is a PHPCS ruleset for [Alley Interactive](https://alley.co). Currently only a proof of concept.

## Installation

To use this standard in a project, declare it as a dependency in the `composer.json` file and then run `composer install`.

```
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/alleyinteractive/Alley-Coding-Standards"
    }
],
"require-dev": {
    "alley/alley-coding-standards": "dev-master"
}
```

The previous example will also install a version of PHPCS to your vendor directory in order to run sniffs locally.

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

You can also pass arguments to the componser phpcs script, following a `--` operator like this:

```
composer run phpcs -- --report=summary
```

## Extending the ruleset
You can create a custom ruleset for your project that extends or customizes these rules by creating your own  `phpcs.xml` or `phpcs.xml.dist` file in your project, which references these rules, like this:

```
<?xml version="1.0"?>
<ruleset>
	<description>Example project ruleset</description>

    <!-- Include Alley Rules -->
    <rule ref="Alley-Interactive" />

    <!-- Project customizations go here -->

</ruleset>
```
