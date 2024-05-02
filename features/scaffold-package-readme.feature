Feature: Scaffold a README.md file for an existing package

  Scenario: Fails when invalid directory provided
    Given an empty directory

    When I try `wp scaffold package-readme bar`
    Then the bar directory should not exist
    And STDERR should be:
      """
      Error: Directory does not exist.
      """
    And the return code should be 1

  Scenario: Fails when invalid package provided
    Given an empty directory
    And a baz/empty file:
      """
      """

    When I try `wp scaffold package-readme baz`
    Then the baz directory should exist
    But the baz/README.md file should not exist
    And STDERR should be:
      """
      Error: Invalid package directory. composer.json file must be present.
      """
    And the return code should be 1

  Scenario: Scaffold a README.md based on the defaults
    Given an empty directory

    When I run `wp package path`
    Then save STDOUT as {PACKAGE_PATH}

    When I run `wp scaffold package wp-cli/default-readme`
    Then STDOUT should contain:
      """
      Success: Created package readme.
      """
    And the {PACKAGE_PATH}/local/wp-cli/default-readme/README.md file should exist
    And the {PACKAGE_PATH}/local/wp-cli/default-readme/README.md file should contain:
      """
      Installing this package requires WP-CLI v2.5 or greater. Update to the latest stable release with `wp cli update`.
      """
    And the {PACKAGE_PATH}/local/wp-cli/default-readme/README.md file should contain:
      """
      [![Build Status](https://travis-ci.org/wp-cli/default-readme.svg?branch=master)
      """
    And the {PACKAGE_PATH}/local/wp-cli/default-readme/README.md file should contain:
      """
      *This README.md is generated dynamically from the project's codebase
      """
    And the {PACKAGE_PATH}/local/wp-cli/default-readme/README.md file should contain:
      """
      wp package install wp-cli/default-readme:dev-master
      """
    When I run `wp package uninstall wp-cli/default-readme`
    Then STDOUT should contain:
      """
      Success: Uninstalled package.
      """

  Scenario: Scaffold a README.md based with custom repository branch
    Given an empty directory

    When I run `wp package path`
    Then save STDOUT as {PACKAGE_PATH}

    When I run `wp scaffold package wp-cli/custom-branch`
    Then STDOUT should contain:
      """
      Success: Created package readme.
      """
    # `wp scaffold package-readme --force` returns a warning
    And I try `wp scaffold package-readme {PACKAGE_PATH}/local/wp-cli/custom-branch --branch=custom --force`
    And the {PACKAGE_PATH}/local/wp-cli/custom-branch/README.md file should exist
    And the {PACKAGE_PATH}/local/wp-cli/custom-branch/README.md file should contain:
      """
      Installing this package requires WP-CLI v2.5 or greater. Update to the latest stable release with `wp cli update`.
      """
    And the {PACKAGE_PATH}/local/wp-cli/custom-branch/README.md file should contain:
      """
      [![Build Status](https://travis-ci.org/wp-cli/custom-branch.svg?branch=custom)
      """
    And the {PACKAGE_PATH}/local/wp-cli/custom-branch/README.md file should contain:
      """
      *This README.md is generated dynamically from the project's codebase
      """

  Scenario: Scaffold a README.md requiring a nightly build
    Given an empty directory

    When I run `wp scaffold package wp-cli/foo --dir=foo --require_wp_cli='>=0.24.0-alpha'`
    Then STDOUT should contain:
      """
      Success: Created package readme.
      """
    And the foo/composer.json file should contain:
      """
          "require": {
              "wp-cli/wp-cli": ">=0.24.0-alpha"
          },
      """
    And the foo/README.md file should exist
    And the foo/README.md file should contain:
      """
      Installing this package requires WP-CLI v0.24.0-alpha or greater. Update to the latest nightly release with `wp cli update --nightly`.
      """
    When I run `wp package uninstall wp-cli/foo`
    Then STDOUT should contain:
      """
      Success: Uninstalled package.
      """

  Scenario: Scaffold a README.md requiring the latest stable release
    Given an empty directory

    When I run `wp scaffold package wp-cli/foo --dir=foo --require_wp_cli='*'`
    Then STDOUT should contain:
      """
      Success: Created package readme.
      """
    And the foo/composer.json file should contain:
      """
          "require": {
              "wp-cli/wp-cli": "*"
          },
      """
    And the foo/README.md file should exist
    And the foo/README.md file should contain:
      """
      Installing this package requires WP-CLI's latest stable release. Update to the latest stable release with `wp cli update`.
      """
    When I run `wp package uninstall wp-cli/foo`
    Then STDOUT should contain:
      """
      Success: Uninstalled package.
      """

  Scenario: Scaffold a readme with custom shields
    Given an empty directory
    And a foo/composer.json file:
      """
      {
          "name": "runcommand/profile",
          "description": "Quickly identify what's slow with WordPress.",
          "homepage": "https://runcommand.io/wp/profile/",
          "license": "GPL-2.0",
          "authors": [],
          "minimum-stability": "dev",
          "autoload": {
              "files": [ "command.php" ]
          },
          "require": {
              "wp-cli/wp-cli": "^2.5"
          },
          "require-dev": {
              "wp-cli/wp-cli-tests": "^3.0.11"
          },
          "extra": {
              "readme": {
                  "shields": [
                    "shield 1",
                    "shield 2",
                    "shield 3"
                  ]
              }
          }
      }
      """

    When I run `wp scaffold package-readme foo`
    Then the foo/README.md file should exist
    And the foo/README.md file should contain:
      """
      shield 1 shield 2 shield 3
      """

  Scenario: Scaffold a readme with a remote support body
    Given an empty directory
    And a foo/composer.json file:
      """
      {
          "name": "runcommand/profile",
          "description": "Quickly identify what's slow with WordPress.",
          "homepage": "https://runcommand.io/wp/profile/",
          "extra": {
              "readme": {
                  "contributing": {
                    "body": "https://gist.githubusercontent.com/danielbachhuber/bb652b1b744cea541705ee9c13605dad/raw/195c17ebb8cf25e947a9df6e02de1e96a084c287/support.md"
                  }
              }
          }
      }
      """

    When I run `wp scaffold package-readme foo`
    Then the foo/README.md file should exist
    And the foo/README.md file should contain:
      """
      ## Contributing

      Support isn't free!
      """

  Scenario: Scaffold a readme with a pre, post and body for the section
    Given an empty directory
    And a foo/composer.json file:
      """
      {
          "name": "runcommand/profile",
          "description": "Quickly identify what's slow with WordPress.",
          "homepage": "https://runcommand.io/wp/profile/",
          "extra": {
              "readme": {
                  "contributing": {
                    "pre": "[Visit Site](https://example.com)",
                    "body": "https://gist.githubusercontent.com/danielbachhuber/bb652b1b744cea541705ee9c13605dad/raw/195c17ebb8cf25e947a9df6e02de1e96a084c287/support.md",
                    "post": "I am after body."
                  }
              }
          }
      }
      """

    When I run `wp scaffold package-readme foo`
    Then the foo/README.md file should exist
    And the foo/README.md file should contain:
      """
      ## Contributing

      [Visit Site](https://example.com)

      Support isn't free!

      I am after body.
      """

  Scenario: Scaffold a readme with custom sections
    Given an empty directory
    And a foo/composer.json file:
      """
      {
          "name": "runcommand/profile",
          "description": "Quickly identify what's slow with WordPress.",
          "homepage": "https://runcommand.io/wp/profile/",
          "extra": {
              "readme": {
                  "sections": [
                    "Installing",
                    "Donating"
                  ],
                  "donating": {
                    "body": "Give me money!"
                  }
              }
          }
      }
      """

    When I run `wp scaffold package-readme foo`
    Then the foo/README.md file should exist
    And the foo/README.md file should contain:
      """
      Quick links: [Installing](#installing) | [Donating](#donating)
      """
    And the foo/README.md file should contain:
      """
      ## Donating

      Give me money!
      """

  Scenario: Scaffold a readme without the powered by
    Given an empty directory
    And a foo/composer.json file:
      """
      {
          "name": "runcommand/profile",
          "description": "Quickly identify what's slow with WordPress.",
          "homepage": "https://runcommand.io/wp/profile/",
          "extra": {
              "readme": {
                  "show_powered_by": false
              }
          }
      }
      """

    When I run `wp scaffold package-readme foo`
    Then the foo/README.md file should exist
    And the foo/README.md file should not contain:
      """
      *This README.md is generated dynamically from the project's codebase
      """

  @broken
  Scenario: Error when commands are specified but not present
    Given an empty directory
    And a foo/composer.json file:
      """
      {
          "name": "runcommand/profile",
          "description": "Quickly identify what's slow with WordPress.",
          "homepage": "https://runcommand.io/wp/profile/",
          "extra": {
              "commands": [
                "profile"
              ]
          }
      }
      """

    When I try `wp scaffold package-readme foo`
    Then STDERR should be:
      """
      Error: Missing one or more commands defined in composer.json -> extra -> commands.
      """
    And the return code should be 1

  Scenario: README for a bundled command
    Given an empty directory
    And a foo/composer.json file:
      """
      {
          "name": "runcommand/profile",
          "authors": [],
          "minimum-stability": "dev",
          "autoload": {
              "files": [ "command.php" ]
          },
          "require": {
          },
          "require-dev": {
              "wp-cli/wp-cli": "*",
              "wp-cli/wp-cli-tests": "^3.0.11"
          },
          "extra": {
              "bundled": true
          }
      }
      """

    When I run `wp scaffold package-readme foo`
    Then the foo/README.md file should exist
    And the foo/README.md file should contain:
      """
      runcommand/profile
      ==================
      """
    And the foo/README.md file should contain:
      """
      This package is included with WP-CLI itself
      """
