# Alley Coding Standards

[![A badge pointing to the readme standard spec](https://img.shields.io/badge/readme%20style-standard-brightgreen.svg?style=flat-square)](https://github.com/RichardLitt/standard-readme)

This is a PHPCS ruleset for [Alley Interactive](https://alley.com).

## Installation

To use this standard in a project, declare it as a dependency.

```bash
composer require --dev alleyinteractive/alley-coding-standards
```

This will install the latest compatible version of PHPCS, WPCS, and VIPCS to your vendor directory in order to run sniffs locally.

You can also manually add this to your project's `composer.json` file as part of the `require` property:

```json
{
	"require": {
		"alleyinteractive/alley-coding-standards": "^2.0"
	}
}
```

## Usage

To use this standard with `phpcs` directly from your command line, use the command:

```bash
vendor/bin/phpcs --standard=Alley-Interactive .
```

Alternatively, you can set this as a composer script, which will automatically reference the correct version of `phpcs` and the dependent standards.

```json
{
	"scripts": {
		"phpcs": "phpcs --standard=Alley-Interactive ."
	}
}
```

Then use the following command:

```bash
composer run phpcs
```

You can also pass arguments to the composer phpcs script, following a `--` operator like this:

```bash
composer run phpcs -- --report=summary
```

### Extending the Ruleset

You can create a custom ruleset for your project that extends or customizes
these rules by creating your own `.phpcs.xml` file in your project, which
references these rules, like this:

```xml
<?xml version="1.0"?>
<ruleset>
  <description>Example project ruleset</description>

  <!-- Include Alley Rules -->
  <rule ref="Alley-Interactive" />

  <!-- Project customizations go here -->
</ruleset>
```

## Custom Sniffs

### Alley.PHP.BlockWrapperAttributes

`get_block_wrapper_attributes()` escapes attribute values, but not the array
keys, which become attribute names. This sniff requires every key to be a string
or integer literal, or to be wrapped entirely in `sanitize_key()`.

When the argument is a variable, the sniff checks every assignment to it in the
enclosing function (or file) before the call, or through the end of the loop if
the call is inside one.

#### UnsafeKey

A key is something other than a literal or a whole `sanitize_key()` call:

```php
// Passes.
echo get_block_wrapper_attributes( [ 'data-id' => $id ] );
echo get_block_wrapper_attributes( [ sanitize_key( $key ) => $value ] );

// Fails with UnsafeKey.
echo get_block_wrapper_attributes( [ $key => $value ] );
echo get_block_wrapper_attributes( [ 'data-' . $key => $value ] );
```

#### UnverifiableKeys

The sniff cannot see the keys. This happens when the argument is not an array
literal or a variable, or when the variable:

- is a function, closure, or arrow function parameter
- is assigned something other than an array literal, or changed with `+=` or `??=`
- is destructured into, with `[ $attrs ] = ...` or `list( ... ) = ...`
- is passed to another function, which could change it by reference (e.g.
  `parse_str()`)
- is a reference, a `global`, or a `foreach` variable
- is never assigned an array in the same scope

```php
// Fails with UnverifiableKeys.
echo get_block_wrapper_attributes( array_merge( $defaults, $extra ) );

function render( $attributes ) {
	return get_block_wrapper_attributes( $attributes );
}
```

This favors false positives over missed issues: a harmless call like
`count( $attrs )` before the call is flagged too. If it is too noisy for your
project, you can exclude that error code alone:

```xml
<rule ref="Alley-Interactive">
  <exclude name="Alley.PHP.BlockWrapperAttributes.UnverifiableKeys" />
</rule>
```

### Alley.PHP.DisallowShortEchoTag

Disallows the short echo tag (`<?=`), even when `short_open_tag` is enabled in
`php.ini`. Use `<?php echo ... ?>` instead.

### Alley.PHP.FilterCallbackTypehint

Flags a typehint on the first parameter of an `add_filter()` callback. Core or
other plugins may pass a different type than the one declared, which causes a
fatal error. Check the type inside the callback instead, or use `mixed`. Only
the first parameter is checked, and `add_action()` callbacks are not checked.
Closures, arrow functions, named functions, array callbacks, and first-class
callables are supported. Violations can be fixed with `phpcbf`.

```php
// Passes.
add_filter( 'the_title', fn( $title ) => (string) $title );
add_filter( 'the_title', fn( mixed $title ) => (string) $title );

// Fails with ParameterTypehint.
add_filter( 'the_title', fn( string $title ) => $title );
```

### Alley.PHP.StrictTypeDeclarationSpacing

Requires `declare(strict_types=1);` with no whitespace inside the parentheses
or around the equals sign. Violations can be fixed with `phpcbf`.

## Testing

When contributing to this project, modifications to the ruleset should have a
corresponding test in the `tests` directory. For the most part, this takes the
form of a passing test in `tests/fixtures/pass` and a failing one in
`tests/fixtures/fail`.

You can run the tests with `composer phpunit`. If you
want to run PHPCS against the test fixtures, you can run
`composer phpcs:fixtures` to ensure that what is passing/failing matches your
expectations.

### Special Considerations for failure expectations
For failing fixtures in `tests/fixtures/fail`, we recommend
keeping the files smaller and focused on the specific sniff being tested.
A corresponding file should be created in `tests/fixtures/fail/expectations`
referencing the expected source of the failures for that test file. Name the file
the same as you do the fixture (e.g. `tests/fixtures/fail/foo.php` and `tests/fixtures/fail/expectations/foo.php`).

To also check where each error is reported, map the source to a list of line
numbers. The test then fails unless that source is reported on exactly those
lines:

```php
return [
	'Alley.PHP.BlockWrapperAttributes.UnsafeKey' => [ 9, 12, 15 ],
];
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley Interactive](https://github.com/alleyinteractive).
Like what you see? [Come work with us](https://alley.com/careers/).

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
