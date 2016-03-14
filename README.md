scaffold-package-command
===============================

Scaffold WP-CLI commands with functional tests

[![Build Status](https://travis-ci.org/wp-cli/scaffold-package-command.svg?branch=master)](https://travis-ci.org/wp-cli/scaffold-package-command)

Quick links: [Installing](#installing) |[Using](#using)| [Contributing](#contributing)

## Installing

This package requires the latest nightly version of WP-CLI. Update with `wp cli update --nightly`.

Once you've done so, you can install this package with `wp package install wp-cli/scaffold-package-command`

## Using

This package implements the following commands:

### wp scaffold package

Generate the files needed for a basic WP-CLI command.

~~~
wp scaffold package <name> [--description=<description>] [--dir=<dir>]
~~~

### wp scaffold package-tests

Generate files needed for writing Behat tests for your command.

~~~
wp scaffold package-tests <dir> [--force]
~~~

### wp scaffold package-readme

Generate a README.md for your command.

~~~
wp scaffold package-readme <dir> [--force]
~~~


## Contributing

Code and ideas are more than welcome.

Please [open an issue](https://github.com/wp-cli/scaffold-package-command/issues) with questions, feedback, and violent dissent. Pull requests are expected to include test coverage.
