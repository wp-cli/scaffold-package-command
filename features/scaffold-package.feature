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
    And the foo/.travis.yml file should not exist

    When I run `wp --require=foo/command.php hello-world`
    Then STDOUT should be:
      """
      Success: Hello world.
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
    And the foo/.travis.yml file should exist

    When I run `wp --require=foo/command.php hello-world`
    Then STDOUT should be:
      """
      Success: Hello world.
      """
