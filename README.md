wp-cli/scaffold-package-command
===============================

Scaffold WP-CLI commands with functional tests

[![Build Status](https://travis-ci.org/wp-cli/scaffold-package-command.svg?branch=master)](https://travis-ci.org/wp-cli/scaffold-package-command) [![CircleCI](https://circleci.com/gh/wp-cli/scaffold-package-command/tree/master.svg?style=svg)](https://circleci.com/gh/wp-cli/scaffold-package-command/tree/master)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing)

## Using

This package implements the following commands:

### wp scaffold package

Generate the files needed for a basic WP-CLI command.

~~~
wp scaffold package <name> [--description=<description>] [--homepage=<homepage>] [--dir=<dir>] [--license=<license>] [--require_wp_cli=<version>] [--skip-tests] [--skip-readme] [--force]
~~~

Default behavior is to create the following files:
- command.php
- composer.json (with package name, description, and license)
- .gitignore, .editorconfig, and .distignore
- README.md (via wp scaffold package-readme)
- Test harness (via wp scaffold package-tests)

Unless specified with `--dir=<dir>`, the command package is placed in the
WP-CLI `packages/local/` directory.

**OPTIONS**

	<name>
		Name for the new package. Expects <author>/<package> (e.g. 'wp-cli/scaffold-package').

	[--description=<description>]
		Human-readable description for the package.

	[--homepage=<homepage>]
		Homepage for the package. Defaults to 'https://github.com/<name>'

	[--dir=<dir>]
		Specify a destination directory for the command. Defaults to WP-CLI's `packages/local/` directory.

	[--license=<license>]
		License for the package.
		---
		default: MIT
		---

	[--require_wp_cli=<version>]
		Required WP-CLI version for the package.
		---
		default: >=0.23.0
		---

	[--skip-tests]
		Don't generate files for integration testing.

	[--skip-readme]
		Don't generate a README.md for the package.

	[--force]
		Overwrite files that already exist.



### wp scaffold package-tests

Generate files needed for writing Behat tests for your command.

~~~
wp scaffold package-tests <dir> [--ci=<provider>] [--force]
~~~

WP-CLI makes use of a Behat-based testing framework, which you should use
too. Functional tests are an integral ingredient of highly-quality,
maintainable commands. Behat is a great choice as a testing framework
because:

* It’s easy to write new tests, which means they’ll actually get written.
* The tests interface with your command in the same manner as your users
interface with your command, and they describe how the command is
expected to work in human-readable terms.

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
`./vendor/bin/behat`. If you find yourself using Behat on a number of
projects and don't want to install a copy with each one, you can
`composer global require behat/behat` to install Behat globally on your
machine. Make sure `~/.composer/vendor/bin` has also been added to your
`$PATH`. Once you've done so, you can run the tests for a project by
calling `behat`.

**ENVIRONMENT**

The `features/bootstrap/FeatureContext.php` file expects the
WP_CLI_BIN_DIR environment variable.

WP-CLI Behat framework uses Behat ~2.5, which is installed with Composer.

**OPTIONS**

	<dir>
		The package directory to generate tests for.

	[--ci=<provider>]
		Create a configuration file for a specific CI provider.
		---
		default: travis
		options:
		  - travis
		  - circle
		---

	[--force]
		Overwrite files that already exist.

**EXAMPLE**

    wp scaffold package-tests /path/to/command/dir/



### wp scaffold package-readme

Generate a README.md for your command.

~~~
wp scaffold package-readme <dir> [--force]
~~~

Creates a README.md with Using, Installing, and Contributing instructions
based on the composer.json file for your WP-CLI package. Run this command
at the beginning of your project, and then every time your usage docs
change.

These command-specific docs are generated based composer.json -> 'extra'
-> 'commands'. For instance, this package's composer.json includes:

```
{
  "name": "wp-cli/scaffold-package-command",
   // [...]
   "extra": {
       "commands": [
           "scaffold package",
           "scaffold package-tests",
           "scaffold package-readme"
       ]
   }
}
```

You can also customize the rendering of README.md generally with
composer.json -> 'extra' -> 'readme'. For example, runcommand/hook's
composer.json includes:

```
{
    "extra": {
        "commands": [
            "hook"
        ],
        "readme": {
            "shields": [
                "[![Build Status](https://travis-ci.org/runcommand/reset-password.svg?branch=master)](https://travis-ci.org/runcommand/reset-password)"
            ],
            "sections": [
                "Using",
                "Installing",
                "Support"
            ],
            "support": {
                "body": "https://raw.githubusercontent.com/runcommand/runcommand-theme/master/bin/readme-partials/support-open-source.md"
            },
            "show_powered_by": false
        }
    }
}
```

In this example:

* "shields" supports arbitrary images as shields to display.
* "sections" permits defining arbitrary sections (instead of default Using, Installing and Contributing).
* "support" -> "body" uses a remote Markdown file as the section contents. This can also be a local file path, or a string.
* "show_powered_by" shows or hides the Powered By mention at the end of the readme.

**OPTIONS**

	<dir>
		Directory of an existing command.

	[--force]
		Overwrite the readme if it already exists.

## Installing

Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with `wp package install wp-cli/scaffold-package-command`.

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/scaffold-package-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/scaffold-package-command/issues/new) with the following:

1. What you were doing (e.g. "When I run `wp post list`").
2. What you saw (e.g. "I see a fatal about a class being undefined.").
3. What you expected to see (e.g. "I expected to see the list of posts.")

Include as much detail as you can, and clear steps to reproduce if possible.

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/scaffold-package-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, please follow our guidelines for creating a pull request to make sure it's a pleasant experience:

1. Create a feature branch for each contribution.
2. Submit your pull request early for feedback.
3. Include functional tests with your changes. [Read the WP-CLI documentation](https://wp-cli.org/docs/pull-requests/#functional-tests) for an introduction.
4. Follow the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/).


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
