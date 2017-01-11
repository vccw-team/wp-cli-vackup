Feature: Test that `wp vackup` commands loads.

  Scenario: `wp vackup` commands should be available.
    Given a WP install

    When I run `wp help vackup`
    Then the return code should be 0

    When I run `wp help vackup`
    Then the return code should be 0

    When I run `wp help vackup restore`
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

  Scenario: Tests for the `wp vackup restore`
    Given a WP install
    And a wp-content/plugins/example.php file:
      """
      <?php
      /*
      Plugin Name: Example Plugin
      Version: 1.2.3
      */
      """
    And I run `wp plugin activate example`
    And I run `wp vackup create --dir=/tmp`
    And save path to the archive as {ARCHIVE_FILE}
    And I run `wp plugin deactivate example`
    And I run `wp plugin uninstall example`
    And the wp-content/plugins/example.php file should not exist

    When I try `wp vackup restore foo/bar/hello.zip`
    Then the return code should be 1
    Then STDERR should contain:
      """
      Error: No such file or directory.
      """

    When I run `wp vackup restore {ARCHIVE_FILE}`
    Then STDOUT should contain:
      """
      Success: Restored from
      """
    And the wp-content/plugins/example.php file should exist

    When I run `wp plugin list`
    Then STDOUT should be a table containing rows:
      | name    | status | update | version |
      | example | active | none   | 1.2.3   |

    When I run `wp core version`
    Then the return code should be 0
