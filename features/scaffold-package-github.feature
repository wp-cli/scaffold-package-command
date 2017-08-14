Feature: Scaffold GitHub configuration for an existing package

  Scenario: Fails when invalid directory provided
    Given an empty directory

    When I try `wp scaffold package-github bar`
    Then the bar directory should not exist
    And STDERR should be:
      """
      Error: Directory does not exist.
      """

  Scenario: Fails when invalid package provided
    Given an empty directory
    And a baz/empty file:
      """
      """

    When I try `wp scaffold package-github baz`
    Then the baz directory should exist
    But the baz/.github directory should not exist
    And STDERR should be:
      """
      Error: Invalid package directory. composer.json file must be present.
      """

  Scenario: Scaffold GitHub configuration based on defaults
    Given an empty directory

    When I run `wp package path`
    Then save STDOUT as {PACKAGE_PATH}

    When I run `wp scaffold package wp-cli/default-github --skip-github`
    Then the {PACKAGE_PATH}/local/wp-cli/default-github directory should exist

    When I run `wp scaffold package-github {PACKAGE_PATH}/local/wp-cli/default-github`
    Then STDOUT should contain:
      """
      Success: Created package GitHub configuration.
      """
    And the {PACKAGE_PATH}/local/wp-cli/default-github/.github directory should exist
    And the {PACKAGE_PATH}/local/wp-cli/default-github/.github/ISSUE_TEMPLATE file should contain:
      """
      Thanks for creating a new issue!
      """
    And the {PACKAGE_PATH}/local/wp-cli/default-github/.github/PULL_REQUEST_TEMPLATE file should contain:
      """
      Thanks for submitting a pull request!
      """
