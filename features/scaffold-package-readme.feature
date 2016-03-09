Feature: Scaffold a README.md file for an existing package

  Scenario: Scaffold a README.md based on the defaults
    Given an empty directory

    When I run `wp scaffold package wp-cli/foo --dir=foo`
    Then STDOUT should contain:
      """
      Success: Created package readme.
      """
    And the foo/README.md file should exist
