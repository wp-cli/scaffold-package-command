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

  Scenario: Scaffolds .travis.yml configuration file by default
    When I run `wp scaffold package-tests community-command`
    Then STDOUT should not be empty
    And the community-command/.travis.yml file should exist
    And the community-command/.travis.yml file should contain:
      """
      bash bin/install-package-tests.sh
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
    And the community-command/.travis.yml file should not exist
