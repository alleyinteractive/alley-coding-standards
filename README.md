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

### `get_block_wrapper_attributes()` keys

`get_block_wrapper_attributes()` escapes attribute values, but the array keys
become attribute names and are output as-is. The `Alley.PHP.BlockWrapperAttributes`
sniff requires every key to be a string or integer literal, or a concatenation
of literals and `sanitize_key()` calls that starts with a literal containing a
hyphen:

```php
$wrapper_attributes = [
	'class'   => 'wp-block-example',
	'data-id' => $attributes['id'], // Values are escaped for you.
];

$wrapper_attributes[ 'data-' . sanitize_key( $key ) ] = $value;

echo get_block_wrapper_attributes( $wrapper_attributes );
```

`sanitize_key()` lowercases the key and strips everything except `a-z`, `0-9`,
`_` and `-`. That stops a key from breaking out of the attribute name, but on its
own it still allows names like `onclick` or `style`, and `esc_attr()` doesn't
make a value safe there: `[ sanitize_key( $key ) => $value ]` with `onmouseover`
and `alert(1)` renders a working event handler. No event handler contains a
hyphen, so a literal prefix like `'data-'` or `'aria-'` rules them out.

The sniff follows the array through inline literals, `array_merge()`,
`array_replace()`, ternaries, `??`, spreads, and writes to variables in the same
function. Every write in the function is checked, even after the call, since a
later write can run first in a loop. Anything it can't verify is an error:

- `UnescapedDynamicKey`: a visible key that isn't provably safe, such as `$key`,
  `"data-{$key}"`, `esc_attr( $key )`, a constant, or a bare `sanitize_key( $key )`.
  Use `'data-' . sanitize_key( $key )`.
- `UnverifiableKeys`: the keys can't be seen. Common causes:
  - a function parameter, including an arrow function's
  - any variable outside a function: block render templates and template parts
    receive variables from the code that includes them, such as the block's
    `$attributes`
  - a closure `use` variable, or a variable captured by reference (`use ( &$attrs )`)
  - a helper's return value or an object property
  - `$a + $b` unions, `array_map()`/`array_filter()` results, chained
    assignments (`$a = $b = [ ... ]`) and destructuring
  - any other use of the variable, such as `count( $attrs )`, `$copy = $attrs`
    or passing it to `array_merge()`
  - `extract()`, `include`/`require`, `eval()`, a variable variable (`$$name`)
    or `$GLOBALS` anywhere in the same function, since
    these can change the array without naming it

#### Where the error is reported

The error isn't always on the `get_block_wrapper_attributes()` line, and a
`phpcs:ignore` only works on the line the error is reported on. Every message
names the line of the `get_block_wrapper_attributes()` call it relates to.

| Cause | Reported on |
| --- | --- |
| Inline key, e.g. `[ $key => 1 ]` | the key |
| Element write, e.g. `$attrs[ $key ] = 1;` | the key in that write |
| Any other use of the variable, e.g. passing it to a function | that use of the variable |
| `extract()`, `include`/`require`, `eval()`, `$$name` or `$GLOBALS` | that construct |
| Untraceable assignment, e.g. `$attrs = alley_get_attrs();` | the assigned value |
| Parameter, variable outside a function, unassigned variable, or a helper result or property passed directly | the argument to `get_block_wrapper_attributes()` |

If you've confirmed the keys are safe, ignore the specific code on that line,
with a reason:

```php
// phpcs:ignore Alley.PHP.BlockWrapperAttributes.UnverifiableKeys -- Keys are hardcoded in alley_get_wrapper_attrs().
echo get_block_wrapper_attributes( alley_get_wrapper_attrs() );

$attrs = [ 'class' => 'example' ];
// phpcs:ignore Alley.PHP.BlockWrapperAttributes.UnverifiableKeys -- alley_add_classes() only changes the class value.
alley_add_classes( $attrs );
echo get_block_wrapper_attributes( $attrs );
```

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

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley Interactive](https://github.com/alleyinteractive).
Like what you see? [Come work with us](https://alley.com/careers/).

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
