Feature: Test that `wp vackup` commands loads.

  Scenario: `wp vackup` commands should be available.
    Given a WP install

    When I run `wp help vackup`
    Then the return code should be 0

    When I run `wp help vackup`
    Then the return code should be 0

    When I run `wp help vackup extract`
    Then the return code should be 0

  Scenario: Tests for `wp vackup create`.
    Given a WP install

    When I run `wp vackup create`
    Then save path to the archive as {ARCHIVE_FILE}
    And STDOUT should contain:
      """
      Success: Archived to
      """
    And STDOUT should contain:
      """
      example.com
      """
    And the {ARCHIVE_FILE} file should exist

    When I run `wp vackup create --dir=/tmp`
    Then save path to the archive as {ARCHIVE_FILE}
    And STDOUT should contain:
      """
      Success: Archived to
      """
    And STDOUT should contain:
      """
      /tmp
      """
    And STDOUT should contain:
      """
      example.com
      """
    And the {ARCHIVE_FILE} file should exist

  Scenario: Tests for the `wp vackup create` with extra config.
    Given a WP install
    And I run `mkdir -p /tmp/backups`
    And a wp-cli.yml file:
      """
      vackup:
        dir: /tmp/backups
      """

    When I run `wp vackup create`
    Then save path to the archive as {ARCHIVE_FILE}
    And STDOUT should contain:
      """
      Success: Archived to
      """
    And STDOUT should contain:
      """
      /tmp/backups
      """
    And STDOUT should contain:
      """
      example.com
      """
    And the {ARCHIVE_FILE} file should exist

    When I run `wp vackup create --dir=/tmp`
    Then save path to the archive as {ARCHIVE_FILE}
    And STDOUT should contain:
      """
      Success: Archived to
      """
    And STDOUT should not contain:
      """
      /tmp/backups
      """
    And STDOUT should contain:
      """
      example.com
      """
    And the {ARCHIVE_FILE} file should exist

  Scenario: Tests for the `wp vackup extract`
    Given a WP install
    And a wp-content/plugins/example.php file:
      """
      // Plugin Name: Example Plugin
      // Network: true
      """
    And I run `wp vackup create --dir=/tmp`
    And save path to the archive as {ARCHIVE_FILE}
    And I run `wp plugin uninstall example`

    When I try `wp vackup extract foo/bar/hello.zip`
    Then the return code should be 1
    Then STDERR should contain:
      """
      Error: No such file or directory.
      """

    When I run `wp vackup extract {ARCHIVE_FILE}`
    Then STDOUT should contain:
      """
      Success: Extracted from
      """
    And the wp-content/plugins/example.php file should exist

    When I run `wp core version`
    Then the return code should be 0
