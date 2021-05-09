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
        }
      }
      """

  Scenario: Fails when invalid directory provided
    Given an empty directory

    When I try `wp scaffold package-tests bar`
    Then the bar directory should not exist
    And STDERR should be:
      """
      Error: Directory does not exist.
      """
    And the return code should be 1

  Scenario: Fails when invalid package provided
    Given an empty directory
    And a baz/empty file:
      """
      """

    When I try `wp scaffold package-tests baz`
    Then the baz directory should exist
    But the baz/features directory should not exist
    And STDERR should be:
      """
      Error: Invalid package directory. composer.json file must be present.
      """
    And the return code should be 1

  Scenario: Scaffold package tests
    Given a invalid-command/command.php file:
      """
      <?php
      """

    When I run `wp scaffold package-tests community-command`
    Then STDOUT should not be empty
    And the community-command/.travis.yml file should exist
      """
      require-wp
      """
    And the community-command/features directory should contain:
      """
      load-wp-cli.feature
      """

    When I try `wp scaffold package-tests invalid-command`
    Then STDERR should be:
      """
      Error: Invalid package directory. composer.json file must be present.
      """
    And the return code should be 1

  Scenario: Scaffolds .travis.yml configuration file by default
    When I run `wp scaffold package-tests community-command`
    Then STDOUT should not be empty
    And the community-command/.travis.yml file should exist
    And the community-command/.travis.yml file should contain:
      """
      - composer prepare-tests
      """
    And the community-command/.travis.yml file should contain:
      """
      - composer behat
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

    When I try `wp scaffold package-tests community-command --force`
    Then the community-command/features/load-wp-cli.feature file should not exist
    And the community-command/features/command.feature file should exist
    And STDERR should contain:
      """
      Warning: File already exists
      """
    And the return code should be 0

  @broken
  Scenario: Scaffolds .travis.yml configuration file with travis[-<tag>[-append]].yml append/override files.
    Given a community-command/travis-cache-append.yml file:
      """
          - $HOME/my-append-cache
      """
    And a community-command/travis-env-append.yml file:
      """
          - MY_APPEND_ENV="my-append-env"
      """
    And a community-command/travis-matrix-append.yml file:
      """
          - php: 99.97
            env: WP_VERSION=9997.9997
      """
    And a community-command/travis-before_install-append.yml file:
      """
        - bash bin/my-append-before_install.sh
        - php -m
      """
    And a community-command/travis-install-append.yml file:
      """
        - bash bin/my-append-install.sh
      """
    And a community-command/travis-before_script-append.yml file:
      """
        - bash bin/my-append-before_script.sh
      """
    And a community-command/travis-script-append.yml file:
      """
        - bash bin/my-append-script.sh
      """
    And a community-command/travis-append.yml file:
      """

      addons:
        apt:
          packages:
          - ghostscript
      """

    When I run `wp scaffold package-tests community-command`
    Then STDOUT should not be empty
    And the community-command/.travis.yml file should exist
    And the community-command/.travis.yml file should contain:
      """
          - $HOME/.composer/cache
      """
    And the community-command/.travis.yml file should contain:
      """
          - $HOME/my-append-cache

      env:
      """
    And the community-command/.travis.yml file should contain:
      """
          - PATH="$TRAVIS_BUILD_DIR/vendor/bin:$PATH"
      """
    And the community-command/.travis.yml file should contain:
      """
          - MY_APPEND_ENV="my-append-env"

      matrix:
      """
    And the community-command/.travis.yml file should contain:
      """
          - php: 7.2
      """
    And the community-command/.travis.yml file should contain:
      """
          - php: 99.97
            env: WP_VERSION=9997.9997

      before_install:
      """
    And the community-command/.travis.yml file should contain:
      """
      # Remove Xdebug
      """
    And the community-command/.travis.yml file should contain:
      """
        - bash bin/my-append-before_install.sh
        - php -m

      install:
      """
    And the community-command/.travis.yml file should contain:
      """
        - bash bin/install-package-tests.sh
      """
    And the community-command/.travis.yml file should contain:
      """
        - bash bin/my-append-install.sh

      before_script:
      """
    And the community-command/.travis.yml file should contain:
      """
        - composer validate
      """
    And the community-command/.travis.yml file should contain:
      """
        - bash bin/my-append-before_script.sh

      script:
      """
    And the community-command/.travis.yml file should contain:
      """
        - bash bin/test.sh
        - bash bin/my-append-script.sh
      """
    And the community-command/.travis.yml file should contain:
      """

      addons:
        apt:
          packages:
          - ghostscript
      """

    Given a community-command/travis-cache.yml file:
      """
      cache:
        directories:
          - $HOME/my-overwrite-cache
      """
    And a community-command/travis-env.yml file:
      """
      env:
        global:
          - MY_OVERWRITE_ENV="my-overwrite-env"
      """
    And a community-command/travis-matrix.yml file:
      """
      matrix:
        include:
          - php: 99.99
            env: WP_VERSION=9999.9999
          - php: 99.98
            env: WP_VERSION=9999.9998
      """
    And a community-command/travis-before_install.yml file:
      """
      before_install:
        - bash bin/my-overwrite-before_install.sh
      """
    And a community-command/travis-install.yml file:
      """
      install:
        - bash bin/my-overwrite-install.sh
        - bash bin/my-overwrite-install2.sh
      """
    And a community-command/travis-before_script.yml file:
      """
      before_script:
        - bash bin/my-overwrite-before_script.sh
      """
    And a community-command/travis-script.yml file:
      """
      script:
        - bash bin/my-overwrite-script.sh
      """

    When I try `wp scaffold package-tests community-command --force`
    Then STDOUT should not be empty
    And the community-command/.travis.yml file should exist
    And the community-command/.travis.yml file should contain:
      """
      cache:
        directories:
          - $HOME/my-overwrite-cache

      env:
        global:
          - MY_OVERWRITE_ENV="my-overwrite-env"

      matrix:
        include:
          - php: 99.99
            env: WP_VERSION=9999.9999
          - php: 99.98
            env: WP_VERSION=9999.9998

      before_install:
        - bash bin/my-overwrite-before_install.sh

      install:
        - bash bin/my-overwrite-install.sh
        - bash bin/my-overwrite-install2.sh

      before_script:
        - bash bin/my-overwrite-before_script.sh

      script:
        - bash bin/my-overwrite-script.sh
      """
    And the community-command/.travis.yml file should contain:
      """

      addons:
        apt:
          packages:
          - ghostscript
      """
    # `travis-matrix.yml` overrides `travis-matrix-append.yml`.
    And the community-command/.travis.yml file should not contain:
      """
      9997
      """
    # `travis-<tag>.yml` overrides `travis-<tag>-append.yml`.
    And the community-command/.travis.yml file should not contain:
      """
      my-append
      """
    # `travis-cache.yml` overrides standard generated content.
    And the community-command/.travis.yml file should not contain:
      """
      .composer
      """
    # `travis-env.yml` overrides standard generated content.
    And the community-command/.travis.yml file should not contain:
      """
      WP_CLI_BIN_DIR
      """
    # `travis-matrix.yml` overrides standard generated content.
    And the community-command/.travis.yml file should not contain:
      """
      7.2
      """
    # `travis-before_install.yml` overrides standard generated content.
    And the community-command/.travis.yml file should not contain:
      """
      # Remove Xdebug
      """
    # `travis-install/before_script.yml` overrides standard generated content.
    And the community-command/.travis.yml file should not contain:
      """
      composer
      """
    # `travis-script.yml` overrides standard generated content.
    And the community-command/.travis.yml file should not contain:
      """
      bin/test.sh
      """
    And STDERR should contain:
      """
      Warning: File already exists
      """
    And the return code should be 0
