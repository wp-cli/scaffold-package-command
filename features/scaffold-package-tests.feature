Feature: Scaffold the test suite for an existing package

  Background:
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

  Scenario: Scaffold package tests
    Given a invalid-command/command.php file:
      """
      <?php
      """

    When I run `wp scaffold package-tests community-command`
    Then STDOUT should not be empty
    And the community-command/.travis.yml file should exist
    And the community-command/bin/install-package-tests.sh file should exist
    And the community-command/bin/test.sh file should exist
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
      ProcessRun.php
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

  Scenario: Scaffolds .travis.yml configuration file by default
    When I run `wp scaffold package-tests community-command`
    Then STDOUT should not be empty
    And the community-command/.travis.yml file should exist
    And the community-command/.travis.yml file should contain:
      """
      bash bin/install-package-tests.sh
      """
    And the community-command/.travis.yml file should contain:
      """
      bash bin/test.sh
      """
    And the community-command/circle.yml file should not exist

  Scenario: Scaffolds .travis.yml configuration file with argument
    When I run `wp scaffold package-tests community-command --ci=circle`
    Then STDOUT should not be empty
    And the community-command/circle.yml file should exist
    And the community-command/circle.yml file should contain:
      """
      bash bin/install-package-tests.sh
      """
    And the community-command/circle.yml file should contain:
      """
      bash bin/test.sh
      """
    And the community-command/.travis.yml file should not exist

  Scenario: Don't scaffold features/load-wp-cli.feature when a feature file already exists
    When I run `wp scaffold package-tests community-command`
    And I run `mv community-command/features/load-wp-cli.feature community-command/features/command.feature`
    Then the community-command/features/load-wp-cli.feature file should not exist
    And the community-command/features/command.feature file should exist

    When I run `wp scaffold package-tests community-command --force`
    Then the community-command/features/load-wp-cli.feature file should not exist
    And the community-command/features/command.feature file should exist
