Feature: Scaffold WP-CLI commands

  Scenario: Scaffold a WP-CLI command without tests
    Given an empty directory

    When I run `WP_CLI_PACKAGES_DIR=packages wp scaffold package wp-cli/foo --skip-tests`
    Then STDOUT should contain:
      """
      Success: Created package files
      """
    And the packages/local/wp-cli/foo/.gitignore file should exist
    And the packages/local/wp-cli/foo/.editorconfig file should exist
    And the packages/local/wp-cli/foo/.distignore file should exist
    And the packages/local/wp-cli/foo/.distignore file should contain:
      """
      .gitignore
      """
    And the packages/local/wp-cli/foo/composer.json file should exist
    And the packages/local/wp-cli/foo/composer.json file should contain:
      """
      "type": "wp-cli-package",
      """
    And the packages/local/wp-cli/foo/composer.json file should contain:
      """
      "homepage": "https://github.com/wp-cli/foo",
      """
    And the packages/local/wp-cli/foo/composer.json file should contain:
      """
      "license": "MIT",
      """
    And the packages/local/wp-cli/foo/composer.json file should contain:
      """
          "require": {
              "wp-cli/wp-cli": ">=0.23.0"
          },
      """
    And the packages/local/wp-cli/foo/command.php file should exist
    And the packages/local/wp-cli/foo/wp-cli.yml file should exist
    And the packages/local/wp-cli/foo/.travis.yml file should not exist

    When I run `WP_CLI_PACKAGES_DIR=packages wp --require=packages/local/wp-cli/foo/command.php hello-world`
    Then STDOUT should be:
      """
      Success: Hello world.
      """

    When I run `cat packages/local/wp-cli/foo/wp-cli.yml`
    Then STDOUT should contain:
      """
      require:
        - command.php
      """

    When I run `cat packages/local/wp-cli/foo/.gitignore`
    Then STDOUT should contain:
      """
      .DS_Store
      """

    When I run `cat packages/local/wp-cli/foo/.editorconfig`
    Then STDOUT should contain:
      """
      This file is for unifying the coding style for different editors and IDEs
      """

  Scenario: Scaffold a package with an invalid name
    Given an empty directory

    When I try `wp scaffold package foo`
    Then STDERR should be:
      """
      Error: 'foo' is an invalid package name. Package scaffold expects '<author>/<package>'.
      """

  Scenario: Scaffold a WP-CLI command to a custom directory
    Given an empty directory

    When I run `WP_CLI_PACKAGES_DIR=packages wp scaffold package wp-cli/foo --dir=foo --skip-tests`
    Then STDOUT should contain:
      """
      Success: Created package files
      """
    And the foo/.gitignore file should exist
    And the foo/.editorconfig file should exist
    And the foo/composer.json file should exist
    And the foo/command.php file should exist
    And the foo/wp-cli.yml file should exist
    And the foo/.travis.yml file should not exist

    When I run `WP_CLI_PACKAGES_DIR=packages wp --require=foo/command.php hello-world`
    Then STDOUT should be:
      """
      Success: Hello world.
      """

  Scenario: Attempt to scaffold the same package twice
    Given an empty directory
    And a session file:
      """
      s
      s
      s
      s
      s
      s
      s
      """

    When I run `WP_CLI_PACKAGES_DIR=packages wp scaffold package wp-cli/foo --skip-tests`
    Then STDOUT should contain:
      """
      Success: Created package files
      """

    When I run `WP_CLI_PACKAGES_DIR=packages wp scaffold package wp-cli/foo --skip-tests < session`
    And STDERR should contain:
      """
      Warning: File already exists
      """
    Then STDOUT should contain:
      """
      All package files were skipped
      """

  Scenario: Scaffold a WP-CLI command with tests
    Given an empty directory

    When I run `WP_CLI_PACKAGES_DIR=packages wp scaffold package wp-cli/foo`
    Then STDOUT should contain:
      """
      Success: Created package files
      """
    And the packages/local/wp-cli/foo/.gitignore file should exist
    And the packages/local/wp-cli/foo/.editorconfig file should exist
    And the packages/local/wp-cli/foo/composer.json file should exist
    And the packages/local/wp-cli/foo/command.php file should exist
    And the packages/local/wp-cli/foo/wp-cli.yml file should exist
    And the packages/local/wp-cli/foo/.travis.yml file should exist
    And the packages/local/wp-cli/foo/features/bootstrap/Process.php file should exist
    And the packages/local/wp-cli/foo/features/bootstrap/ProcessRun.php file should exist

    When I run `WP_CLI_PACKAGES_DIR=packages wp --require=packages/local/wp-cli/foo/command.php hello-world`
    Then STDOUT should be:
      """
      Success: Hello world.
      """

  Scenario: Scaffold a command with a custom homepage
    Given an empty directory

    When I run `WP_CLI_PACKAGES_DIR=packages wp scaffold package wp-cli/bar --homepage='http://apple.com'`
    Then STDOUT should contain:
      """
      Success: Created package files
      """
    And the packages/local/wp-cli/bar/composer.json file should exist
    And the packages/local/wp-cli/bar/composer.json file should contain:
      """
      "homepage": "http://apple.com",
      """
