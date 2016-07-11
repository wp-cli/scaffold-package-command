Feature: Scaffold a README.md file for an existing package

  Scenario: Scaffold a README.md based on the defaults
    Given an empty directory

    When I run `wp scaffold package wp-cli/foo --dir=foo`
    Then STDOUT should contain:
      """
      Success: Created package readme.
      """
    And the foo/README.md file should exist
    And the foo/README.md file should contain:
      """
      Installing this package requires WP-CLI v0.23.0 or greater. Update to the latest stable release with `wp cli update`.
      """

  Scenario: Scaffold a README.md requiring a nightly build
    Given an empty directory

    When I run `wp scaffold package wp-cli/foo --dir=foo --require_wp_cli='~0.24.0-alpha'`
    Then STDOUT should contain:
      """
      Success: Created package readme.
      """
    And the foo/composer.json file should contain:
      """
          "require": {
              "wp-cli/wp-cli": "~0.24.0-alpha"
          },
      """
    And the foo/README.md file should exist
    And the foo/README.md file should contain:
      """
      Installing this package requires WP-CLI v0.24.0-alpha or greater. Update to the latest nightly release with `wp cli update --nightly`.
      """
