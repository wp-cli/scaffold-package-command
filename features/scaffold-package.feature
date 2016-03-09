Feature: Scaffold WP-CLI commands

  Scenario: Scaffold a WP-CLI command without tests
    Given an empty directory

    When I run `WP_CLI_PACKAGES_DIR=packages wp scaffold package wp-cli/foo --skip-tests`
    Then STDOUT should contain:
      """
      Success: Created package files.
      """
    And the packages/vendor/wp-cli/foo/.gitignore file should exist
    And the packages/vendor/wp-cli/foo/.editorconfig file should exist
    And the packages/vendor/wp-cli/foo/composer.json file should exist
    And the packages/vendor/wp-cli/foo/command.php file should exist
    And the packages/vendor/wp-cli/foo/wp-cli.yml file should exist
    And the packages/vendor/wp-cli/foo/.travis.yml file should not exist

    When I run `WP_CLI_PACKAGES_DIR=packages wp --require=packages/vendor/wp-cli/foo/command.php hello-world`
    Then STDOUT should be:
      """
      Success: Hello world.
      """

    When I run `cat packages/vendor/wp-cli/foo/wp-cli.yml`
    Then STDOUT should contain:
      """
      require:
        - command.php
      """

    When I run `cat packages/vendor/wp-cli/foo/.gitignore`
    Then STDOUT should contain:
      """
      .DS_Store
      """

    When I run `cat packages/vendor/wp-cli/foo/.editorconfig`
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
      Success: Created package files.
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
      """

    When I run `WP_CLI_PACKAGES_DIR=packages wp scaffold package wp-cli/foo --skip-tests`
    Then STDOUT should contain:
      """
      Success: Created package files.
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
      Success: Created package files.
      """
    And the packages/vendor/wp-cli/foo/.gitignore file should exist
    And the packages/vendor/wp-cli/foo/.editorconfig file should exist
    And the packages/vendor/wp-cli/foo/composer.json file should exist
    And the packages/vendor/wp-cli/foo/command.php file should exist
    And the packages/vendor/wp-cli/foo/wp-cli.yml file should exist
    And the packages/vendor/wp-cli/foo/.travis.yml file should exist

    When I run `WP_CLI_PACKAGES_DIR=packages wp --require=packages/vendor/wp-cli/foo/command.php hello-world`
    Then STDOUT should be:
      """
      Success: Hello world.
      """

  Scenario: Scaffold package tests
    Given a WP install
    Given a community-command/command.php file:
      """
      <?php
      """
    And a community-command/composer.json file:
      """
      {
        "name": "wp-cli/community-command",
        "description": "A demo community command.",
        "license": "MIT",
        "minimum-stability": "dev",
        "require": {
        },
        "autoload": {
          "files": [ "dictator.php" ]
        },
        "require-dev": {
          "behat/behat": "~2.5"
        }
      }
      """
    And a invalid-command/command.php file:
      """
      <?php
      """

    When I run `wp scaffold package-tests community-command`
    Then STDOUT should not be empty
    And the community-command/.travis.yml file should exist
    And the community-command/bin/install-package-tests.sh file should exist
    And the community-command/utils/behat-tags.php file should contain:
      """
      require-wp
      """
    And the community-command/features directory should contain:
      """
      bootstrap
      extra
      load-wp-cli.feature
      steps
      """
    And the community-command/features/bootstrap directory should contain:
      """
      FeatureContext.php
      Process.php
      support.php
      utils.php
      """
    And the community-command/features/steps directory should contain:
      """
      given.php
      then.php
      when.php
      """
    And the community-command/features/extra directory should contain:
      """
      no-mail.php
      """

    When I run `wp eval "if ( is_executable( 'community-command/bin/install-package-tests.sh' ) ) { echo 'executable'; } else { exit( 1 ); }"`
    Then STDOUT should be:
      """
      executable
      """

    When I try `wp scaffold package-tests invalid-command`
    Then STDERR should be:
      """
      Error: Invalid package directory. composer.json file must be present.
      """
