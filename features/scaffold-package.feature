Feature: Scaffold WP-CLI commands

  Scenario: Scaffold a WP-CLI command without tests
    Given an empty directory

    When I run `wp scaffold package foo --skip-tests`
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

    When I run `wp --require=foo/command.php hello-world`
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
      """

    When I run `wp scaffold package foo --skip-tests`
    Then STDOUT should contain:
      """
      Success: Created package files.
      """

    When I run `wp scaffold package foo --skip-tests < session`
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

    When I run `wp scaffold package foo`
    Then STDOUT should contain:
      """
      Success: Created package files.
      """
    And the foo/.gitignore file should exist
    And the foo/.editorconfig file should exist
    And the foo/composer.json file should exist
    And the foo/command.php file should exist
    And the foo/wp-cli.yml file should exist
    And the foo/.travis.yml file should exist

    When I run `wp --require=foo/command.php hello-world`
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
