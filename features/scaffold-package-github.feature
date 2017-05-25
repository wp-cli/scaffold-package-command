Feature: Scaffold GitHub configuration for an existing package

  Scenario: Fails when invalid package directory provided
    Given an empty directory

    When I run `wp package path`
    Then save STDOUT as {PACKAGE_PATH}

    When I try `wp scaffold package-github {PACKAGE_PATH}/local/wp-cli/default-github`
    Then STDERR should be:
      """
      Error: Invalid package directory. composer.json file must be present.
      """

  Scenario: Scaffold GitHub configuration based on defaults
    Given an empty directory

    When I run `wp package path`
    Then save STDOUT as {PACKAGE_PATH}

    When I run `wp scaffold package wp-cli/default-github`
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
