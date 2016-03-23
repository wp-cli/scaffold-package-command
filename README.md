wp-cli/scaffold-package-command
===============================

Scaffold WP-CLI commands with functional tests

[![Build Status](https://travis-ci.org/wp-cli/scaffold-package-command.svg?branch=master)](https://travis-ci.org/wp-cli/scaffold-package-command)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing)

## Using

This package implements the following commands:

### wp scaffold package

Generate the files needed for a basic WP-CLI command.

~~~
wp scaffold package <name> [--description=<description>] [--dir=<dir>] [--license=<license>] [--skip-tests] [--skip-readme] [--force]
~~~

Default behavior is to create the following files:
- command.php
- composer.json (with package name, description, and license)
- .gitignore and .editorconfig
- README.md (via wp scaffold package-readme)
- Test harness (via wp scaffold package-tests)

Unless specified with `--dir=<dir>`, the command package is placed in the
WP-CLI package directory.

**OPTIONS**

	<name>
		Name for the new package. Expects <author>/<package> (e.g. 'wp-cli/scaffold-package').

	[--description=<description>]
		Human-readable description for the package.

	[--dir=<dir>]
		Specify a destination directory for the command. Defaults to WP-CLI's packages directory.

	[--license=<license>]
		License for the package. Default: MIT.

	[--skip-tests]
		Don't generate files for integration testing.

	[--skip-readme]
		Don't generate a README.md for the package.

	[--force]
		Overwrite files that already exist.



### wp scaffold package-tests

Generate files needed for writing Behat tests for your command.

~~~
wp scaffold package-tests <dir> [--force]
~~~

WP-CLI makes use of a Behat-based testing framework, which you should use
too. Behat is a great choice for your WP-CLI commands because:

* It’s easy to write new tests, which means they’ll actually get written.
* The tests interface with your command in the same manner as your users
interface with your command.

Behat tests live in the `features/` directory of your project. When you
use this command, it will generate a default test that looks like this:

```
Feature: Test that WP-CLI loads.

  Scenario: WP-CLI loads for your tests
    Given a WP install

    When I run `wp eval 'echo "Hello world.";'`
    Then STDOUT should contain:
      """
      Hello world.
      """
```

Functional tests typically follow this pattern:

* **Given** some background,
* **When** a user performs a specific action,
* **Then** the end result should be X (and Y and Z).

This command generates all of the files needed for you to write Behat
tests for your own command. Specifically:

* `.travis.yml` is the configuration file for Travis CI.
* `bin/install-package-tests.sh` will configure your environment to run
the tests.
* `features/load-wp-cli.feature` is a basic test to confirm WP-CLI can
load.
* `features/bootstrap`, `features/steps`, `features/extra` are Behat
configuration files.

After running `bin/install-package-tests.sh`, you can run the tests with
`./vendor/bin/behat`

**ENVIRONMENT**

The `features/bootstrap/FeatureContext.php` file expects the
WP_CLI_BIN_DIR environment variable.

WP-CLI Behat framework uses Behat ~2.5.

**OPTIONS**

	<dir>
		The package directory to generate tests for.

	[--force]
		Overwrite files that already exist.

**EXAMPLE**

    wp scaffold package-tests /path/to/command/dir/



### wp scaffold package-readme

Generate a README.md for your command.

~~~
wp scaffold package-readme <dir> [--force]
~~~

Creates a README.md with Installing, Using, and Contributing instructions
based on the composer.json file for your WP-CLI package.

Command-specific docs are generated based composer.json -> 'extras'
-> 'commands'.

**OPTIONS**

	<dir>
		Directory of an existing command.

	[--force]
		Overwrite the readme if it already exists.



## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install wp-cli/scaffold-package-command`

## Contributing

Code and ideas are more than welcome.

Please [open an issue](https://github.com/wp-cli/scaffold-package-command/issues) with questions, feedback, and violent dissent. Pull requests are expected to include test coverage.
